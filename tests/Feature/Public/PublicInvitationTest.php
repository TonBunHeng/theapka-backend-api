<?php

use App\Enums\RsvpStatus;
use App\Models\GiftRecord;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\InvitationSend;
use App\Models\User;
use App\Models\Wedding;
use App\Models\Wish;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('public can view generic invitation without token or authentication', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'slug' => 'sok-dara-2026',
        'title' => 'Sok & Dara Wedding',
    ]);

    Invitation::create([
        'wedding_id' => $wedding->id,
        'title' => 'Sok & Dara Wedding Invitation',
        'slug' => $wedding->slug,
        'status' => 'published',
        'content' => ['welcome' => 'Welcome to our special day!'],
    ]);

    // Public request with no Authorization header
    $response = $this->getJson('/api/public/invitation/sok-dara-2026')
        ->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'wedding' => ['title', 'slug', 'wedding_date'],
            'invitation' => ['title', 'content'],
            'guest',
        ],
    ]);

    expect($response->json('data.guest'))->toBeNull()
        ->and($response->json('data.wedding.title'))->toBe('Sok & Dara Wedding');
});

test('public can view personalized invitation with guest token, updates opened_at and view_count', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'slug' => 'sok-dara-2026',
    ]);

    $invitation = Invitation::create([
        'wedding_id' => $wedding->id,
        'title' => 'Wedding Invitation',
        'slug' => $wedding->slug,
        'status' => 'published',
        'view_count' => 0,
    ]);

    $guest = Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'Borey Kem',
        'token' => 'guest-token-123',
        'side' => 'groom',
        'seats' => 2,
        'rsvp_status' => RsvpStatus::PENDING,
    ]);

    $send = InvitationSend::create([
        'invitation_id' => $invitation->id,
        'guest_id' => $guest->id,
        'channel' => 'link',
        'status' => 'delivered',
        'opened_at' => null,
    ]);

    $response = $this->getJson("/api/public/invitation/sok-dara-2026/{$guest->token}")
        ->assertOk();

    expect($response->json('data.guest.name'))->toBe('Borey Kem')
        ->and($response->json('data.guest.seats'))->toBe(2);

    // View count incremented
    expect($invitation->fresh()->view_count)->toBe(1);

    // Send marked opened_at
    expect($send->fresh()->opened_at)->not->toBeNull();
});

test('public rsvp rejects attending count exceeding reserved seats', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    $guest = Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'Vannak Heng',
        'token' => 'token-seats-test',
        'seats' => 2,
        'rsvp_status' => RsvpStatus::PENDING,
    ]);

    // Attempt to RSVP for 3 seats when only 2 are reserved
    $this->postJson("/api/public/invitation/{$guest->token}/rsvp", [
        'status' => 'attending',
        'attending_count' => 3,
        'notes' => 'Bringing extra friends',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['attending_count']);

    // RSVP within limit
    $this->postJson("/api/public/invitation/{$guest->token}/rsvp", [
        'status' => 'attending',
        'attending_count' => 2,
    ])->assertCreated()
      ->assertJsonPath('data.status', 'attending')
      ->assertJsonPath('data.attending_count', 2);

    $this->assertDatabaseHas('rsvps', [
        'guest_id' => $guest->id,
        'attending_count' => 2,
    ]);
});

test('public wish submission respects moderation and only visible wishes are listed', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'slug' => 'wish-wedding-test',
    ]);

    Invitation::create([
        'wedding_id' => $wedding->id,
        'slug' => $wedding->slug,
        'title' => 'Wish Test',
        'is_moderation_enabled' => false,
    ]);

    $guest = Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'Piseth Chhem',
        'token' => 'token-wish-1',
    ]);

    // Submit public wish
    $this->postJson("/api/public/invitation/{$guest->token}/wish", [
        'sender_name' => 'Piseth Chhem',
        'message' => 'Congratulations to both of you!',
    ])->assertCreated()
      ->assertJsonPath('data.is_visible', true);

    // Create a hidden wish directly
    Wish::create([
        'wedding_id' => $wedding->id,
        'sender_name' => 'Spammer',
        'message' => 'Inappropriate message',
        'is_visible' => false,
    ]);

    // Query wishes listing
    $listResponse = $this->getJson("/api/public/invitation/{$wedding->slug}/wishes")
        ->assertOk();

    $messages = collect($listResponse->json('data'))->pluck('message')->all();

    expect($messages)->toContain('Congratulations to both of you!')
        ->and($messages)->not->toContain('Inappropriate message');
});

test('public invitation endpoint never leaks private guest contacts or gift ledger records', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'slug' => 'leak-prevention-test',
    ]);

    Invitation::create([
        'wedding_id' => $wedding->id,
        'slug' => $wedding->slug,
        'title' => 'Leak Check',
    ]);

    Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'Secret VIP Guest',
        'phone' => '+85599999999',
        'email' => 'vip@secret.com',
        'token' => 'token-vip-1',
    ]);

    GiftRecord::create([
        'wedding_id' => $wedding->id,
        'client_uuid' => (string) \Illuminate\Support\Str::uuid(),
        'giver_name' => 'Rich Uncle',
        'amount' => 5000.00,
        'currency' => \App\Enums\GiftCurrency::USD,
        'method' => 'cash',
        'entry_type' => \App\Enums\GiftEntryType::GIFT,
        'recorded_by' => $couple->id,
        'recorded_at' => now(),
    ]);

    $response = $this->getJson("/api/public/invitation/{$wedding->slug}")
        ->assertOk();

    $raw = $response->getContent();

    expect($raw)->not->toContain('+85599999999')
        ->and($raw)->not->toContain('vip@secret.com')
        ->and($raw)->not->toContain('Rich Uncle')
        ->and($raw)->not->toContain('5000');
});

test('new wedding without pre-existing invitation can be previewed/viewed immediately', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'slug' => 'new-couple-preview-test',
        'title' => 'New Couple Wedding',
        'venue_map_url' => 'https://maps.google.com/?q=11.6685,104.9452',
        'settings' => ['lat' => 11.6685, 'lng' => 104.9452],
    ]);

    // Request public invitation immediately without manual invitation creation
    $response = $this->getJson('/api/public/invitation/new-couple-preview-test')
        ->assertOk();

    expect($response->json('data.wedding.slug'))->toBe('new-couple-preview-test')
        ->and($response->json('data.wedding.map_url'))->toBe('https://maps.google.com/?q=11.6685,104.9452')
        ->and($response->json('data.wedding.lat'))->toBe(11.6685)
        ->and($response->json('data.wedding.lng'))->toBe(104.9452);
});
