<?php

use App\Enums\RoleName;
use App\Enums\WeddingStatus;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Invitation;
use App\Models\Schedule;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingDetail;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('couple can view dashboard summary', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'wedding_date' => now()->addDays(30),
    ]);
    WeddingDetail::create(['wedding_id' => $wedding->id]);
    Invitation::create(['wedding_id' => $wedding->id, 'slug' => $wedding->slug, 'title' => $wedding->title]);

    Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'Friend 1',
        'seats' => 2,
    ]);

    $response = $this->actingAs($couple)
        ->getJson('/api/user/dashboard')
        ->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'wedding',
            'days_left',
            'guest_stats' => ['total_guests', 'total_seats', 'attending', 'declined', 'maybe', 'pending'],
            'gift_stats' => ['KHR', 'USD'],
            'invitation',
        ],
    ]);

    expect($response->json('data.guest_stats.total_guests'))->toBe(1)
        ->and($response->json('data.guest_stats.total_seats'))->toBe(2);
});

test('couple can update wedding details and profile', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);
    WeddingDetail::create(['wedding_id' => $wedding->id, 'groom_name' => 'Old Groom', 'bride_name' => 'Old Bride']);

    $response = $this->actingAs($couple)
        ->putJson('/api/user/wedding', [
            'title' => 'Updated Wedding Title',
            'wedding_date' => '2026-12-25',
            'venue_name' => 'Sokha Hotel Phnom Penh',
            'venue_address' => 'Street 123, Phnom Penh',
            'details' => [
                'groom_name' => 'Sok Chan',
                'bride_name' => 'Sreymom Keo',
                'story' => 'Met at university.',
                'dress_code' => 'Traditional Khmer Formal',
            ],
        ])->assertOk();

    expect($response->json('data.title'))->toBe('Updated Wedding Title')
        ->and($response->json('data.details.groom_name'))->toBe('Sok Chan');

    $this->assertDatabaseHas('weddings', [
        'id' => $wedding->id,
        'title' => 'Updated Wedding Title',
        'venue_name' => 'Sokha Hotel Phnom Penh',
    ]);
});

test('couple can manage wedding schedules CRUD', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    // Create schedule
    $createRes = $this->actingAs($couple)
        ->postJson('/api/user/schedules', [
            'title' => 'Morning Ring Exchange Ceremony',
            'description' => 'Khmer traditional ceremony with monks and elders',
            'start_time' => '07:30',
            'end_time' => '10:00',
            'location' => 'Grand Ballroom',
            'order' => 1,
        ])->assertCreated();

    $scheduleId = $createRes->json('data.id');
    expect($createRes->json('data.title'))->toBe('Morning Ring Exchange Ceremony');

    // List schedules
    $listRes = $this->actingAs($couple)
        ->getJson('/api/user/schedules')
        ->assertOk();
    expect(count($listRes->json('data')))->toBe(1);

    // Update schedule
    $this->actingAs($couple)
        ->putJson("/api/user/schedules/{$scheduleId}", [
            'title' => 'Updated Ceremony Title',
            'start_time' => '08:00',
        ])->assertOk()
          ->assertJsonPath('data.title', 'Updated Ceremony Title');

    // Delete schedule
    $this->actingAs($couple)
        ->deleteJson("/api/user/schedules/{$scheduleId}")
        ->assertOk();

    expect(Schedule::find($scheduleId))->toBeNull();
});

test('couple can manage guest groups and guests CRUD', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    // Create group
    $groupRes = $this->actingAs($couple)
        ->postJson('/api/user/guest-groups', [
            'name' => 'Close Friends',
            'color' => '#FF5733',
            'order' => 1,
        ])->assertCreated();

    $groupId = $groupRes->json('data.id');

    // Create guest
    $guestRes = $this->actingAs($couple)
        ->postJson('/api/user/guests', [
            'group_id' => $groupId,
            'name' => 'Chantha Voeun',
            'phone' => '+85512998877',
            'side' => 'groom',
            'seats' => 2,
            'notes' => 'Table 5 VIP',
        ])->assertCreated();

    $guestId = $guestRes->json('data.id');
    $token = $guestRes->json('data.token');

    expect($token)->not->toBeEmpty()
        ->and($guestRes->json('data.seats'))->toBe(2);

    // Update guest
    $this->actingAs($couple)
        ->putJson("/api/user/guests/{$guestId}", [
            'seats' => 3,
        ])->assertOk()
          ->assertJsonPath('data.seats', 3);

    // List guests
    $listRes = $this->actingAs($couple)
        ->getJson('/api/user/guests')
        ->assertOk();
    expect(count($listRes->json('data')))->toBe(1);

    // Delete guest
    $this->actingAs($couple)
        ->deleteJson("/api/user/guests/{$guestId}")
        ->assertOk();

    expect(Guest::count())->toBe(0);
});

test('guest csv import preview and commit flow', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    $csvContent = "name,phone,side,seats,notes\n"
        . "Rithy Chea,+85512888999,groom,2,High school friend\n"
        . "Sophea Yin,+85512777888,bride,1,Colleague\n";

    $file = UploadedFile::fake()->createWithContent('guests.csv', $csvContent);

    // Preview
    $previewRes = $this->actingAs($couple)
        ->postJson('/api/user/guests/import/preview', [
            'file' => $file,
        ])->assertOk();

    $previewRes->assertJsonStructure([
        'data' => [
            'total_rows',
            'valid_rows',
            'invalid_rows',
            'rows',
        ],
    ]);

    expect($previewRes->json('data.total_rows'))->toBe(2)
        ->and($previewRes->json('data.invalid_rows'))->toBe(0);

    $validRows = collect($previewRes->json('data.rows'))->where('is_valid', true)->map(function ($item) {
        return [
            'name' => $item['data']['name'],
            'phone' => $item['data']['phone'],
            'side' => $item['data']['side'],
            'seats' => $item['data']['seats'],
            'notes' => $item['data']['notes'],
        ];
    })->values()->all();

    // Commit
    $commitRes = $this->actingAs($couple)
        ->postJson('/api/user/guests/import/commit', [
            'rows' => $validRows,
        ])->assertOk();

    expect($commitRes->json('data.imported_count'))->toBe(2);

    expect(Guest::count())->toBe(2);
    $this->assertDatabaseHas('guests', [
        'wedding_id' => $wedding->id,
        'name' => 'Rithy Chea',
        'seats' => 2,
    ]);
});

test('couple can publish and unpublish their digital invitation', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);
    $template = Template::factory()->create(['status' => 'published']);

    $invitation = Invitation::create([
        'wedding_id' => $wedding->id,
        'template_id' => $template->id,
        'slug' => $wedding->slug,
        'title' => $wedding->title,
        'status' => 'draft',
    ]);

    // Publish
    $this->actingAs($couple)
        ->postJson('/api/user/invitation/publish')
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    expect($invitation->fresh()->status)->toBe('published');

    // Unpublish
    $this->actingAs($couple)
        ->postJson('/api/user/invitation/unpublish')
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');

    expect($invitation->fresh()->status)->toBe('draft');
});

test('couple can generate QR codes for guests and wedding', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id, 'slug' => 'qr-wedding-slug']);

    $guest = Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'QR Guest',
        'token' => 'guest-qr-token',
        'seats' => 1,
    ]);

    // Guest QR code
    $guestQrRes = $this->actingAs($couple)
        ->getJson("/api/user/qr-code/guest/{$guest->id}")
        ->assertOk();

    expect($guestQrRes->json('data.qr_code'))->toStartWith('data:image/')
        ->and($guestQrRes->json('data.url'))->toContain('guest-qr-token');

    // Wedding generic QR code
    $weddingQrRes = $this->actingAs($couple)
        ->getJson('/api/user/qr-code/wedding')
        ->assertOk();

    expect($weddingQrRes->json('data.qr_code'))->toStartWith('data:image/')
        ->and($weddingQrRes->json('data.url'))->toContain('qr-wedding-slug');
});

test('couple can update profile and password', function () {
    $couple = User::factory()->asUser()->create([
        'name' => 'Initial Name',
        'password' => bcrypt('OldPassword123!'),
    ]);

    // Update profile
    $this->actingAs($couple)
        ->putJson('/api/user/profile', [
            'name' => 'New Couple Name',
            'phone' => '+85512334455',
        ])->assertOk()
          ->assertJsonPath('data.name', 'New Couple Name');

    // Update password
    $this->actingAs($couple)
        ->putJson('/api/user/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSuperSecretPass456!',
            'password_confirmation' => 'NewSuperSecretPass456!',
        ])->assertOk();

    expect(Hash::check('NewSuperSecretPass456!', $couple->fresh()->password))->toBeTrue();
});
