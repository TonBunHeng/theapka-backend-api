<?php

use App\Enums\GiftCurrency;
use App\Enums\GiftEntryType;
use App\Models\GiftRecord;
use App\Models\User;
use App\Models\Wedding;
use App\Services\GiftLedgerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('idempotent gift creation returns the existing row when client_uuid is duplicated', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    $clientUuid = (string) Str::uuid();

    $payload = [
        'client_uuid' => $clientUuid,
        'giver_name' => 'Lok Oknha Chan',
        'amount' => 100.00,
        'currency' => 'USD',
        'method' => 'cash',
        'notes' => 'Envelope received at table 1',
    ];

    // First POST: returns 201 Created
    $response1 = $this->actingAs($couple)->postJson('/api/user/gifts', $payload);
    $response1->assertStatus(201);
    $giftId = $response1->json('data.id');

    expect(GiftRecord::count())->toBe(1);

    // Second POST with identical client_uuid: returns 200 OK with the SAME gift record
    $response2 = $this->actingAs($couple)->postJson('/api/user/gifts', $payload);
    $response2->assertStatus(200);

    expect($response2->json('data.id'))->toBe($giftId)
        ->and(GiftRecord::count())->toBe(1);
});

test('corrections adjust the gift totals correctly and never mix KHR and USD', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    // 1. Initial USD gift: $100
    $gift1 = GiftRecord::create([
        'wedding_id' => $wedding->id,
        'client_uuid' => (string) Str::uuid(),
        'giver_name' => 'John Doe',
        'amount' => 100.00,
        'currency' => GiftCurrency::USD,
        'method' => 'cash',
        'entry_type' => GiftEntryType::GIFT,
        'recorded_by' => $couple->id,
        'recorded_at' => now(),
    ]);

    // 2. Initial KHR gift: 200,000 KHR
    $gift2 = GiftRecord::create([
        'wedding_id' => $wedding->id,
        'client_uuid' => (string) Str::uuid(),
        'giver_name' => 'Sok San',
        'amount' => 200000.00,
        'currency' => GiftCurrency::KHR,
        'method' => 'cash',
        'entry_type' => GiftEntryType::GIFT,
        'recorded_by' => $couple->id,
        'recorded_at' => now(),
    ]);

    // 3. Correction on USD gift: -$20 adjustment (actual gift was $80)
    $responseCorrection = $this->actingAs($couple)->postJson('/api/user/gifts', [
        'client_uuid' => (string) Str::uuid(),
        'giver_name' => 'John Doe (Correction)',
        'amount' => -20.00,
        'currency' => 'USD',
        'method' => 'cash',
        'entry_type' => 'correction',
        'corrects_id' => $gift1->id,
    ]);
    $responseCorrection->assertStatus(201);

    // 4. Correction on KHR gift: +40,000 KHR adjustment
    $responseCorrectionKhr = $this->actingAs($couple)->postJson('/api/user/gifts', [
        'client_uuid' => (string) Str::uuid(),
        'giver_name' => 'Sok San (Correction)',
        'amount' => 40000.00,
        'currency' => 'KHR',
        'method' => 'cash',
        'entry_type' => 'correction',
        'corrects_id' => $gift2->id,
    ]);
    $responseCorrectionKhr->assertStatus(201);

    // Fetch summary via API
    $summaryResponse = $this->actingAs($couple)->getJson('/api/user/gifts/summary');
    $summaryResponse->assertStatus(200);

    $data = $summaryResponse->json('data');

    // USD Total must be 100 - 20 = 80.00
    expect((float) $data['USD'])->toBe(80.00)
        // KHR Total must be 200,000 + 40,000 = 240,000.00
        ->and((float) $data['KHR'])->toBe(240000.00);

    // Ensure currencies are never added together
    expect($data['USD'])->not->toBe($data['KHR']);
});

test('GiftRecordObserver prevents model update and delete operations', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    $gift = GiftRecord::create([
        'wedding_id' => $wedding->id,
        'client_uuid' => (string) Str::uuid(),
        'giver_name' => 'Guest Observer Test',
        'amount' => 50.00,
        'currency' => GiftCurrency::USD,
        'method' => 'cash',
        'entry_type' => GiftEntryType::GIFT,
        'recorded_by' => $couple->id,
        'recorded_at' => now(),
    ]);

    // Updating must throw RuntimeException
    expect(fn () => $gift->update(['amount' => 100.00]))
        ->toThrow(RuntimeException::class, 'Gift records are immutable and append-only. Updates are strictly forbidden.');

    // Deleting must throw RuntimeException
    expect(fn () => $gift->delete())
        ->toThrow(RuntimeException::class, 'Gift records are immutable and append-only. Deletions are strictly forbidden.');
});

test('no PUT, PATCH, or DELETE route exists for gift records', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    $gift = GiftRecord::create([
        'wedding_id' => $wedding->id,
        'client_uuid' => (string) Str::uuid(),
        'giver_name' => 'Route Test',
        'amount' => 50.00,
        'currency' => GiftCurrency::USD,
        'method' => 'cash',
        'entry_type' => GiftEntryType::GIFT,
        'recorded_by' => $couple->id,
        'recorded_at' => now(),
    ]);

    $this->actingAs($couple);

    // Testing PUT route
    $this->putJson("/api/user/gifts/{$gift->id}", ['amount' => 100])
        ->assertNotFound();

    // Testing PATCH route
    $this->patchJson("/api/user/gifts/{$gift->id}", ['amount' => 100])
        ->assertNotFound();

    // Testing DELETE route
    $this->deleteJson("/api/user/gifts/{$gift->id}")
        ->assertNotFound();
});
