<?php

use App\Enums\RoleName;
use App\Enums\WeddingStatus;
use App\Models\Media;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingDetail;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

test('couple can upload base64 canvas image from theapka-user gallery', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'status' => WeddingStatus::PUBLISHED,
    ]);
    WeddingDetail::create(['wedding_id' => $wedding->id]);

    // 1x1 transparent PNG data URI
    $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    $response = $this->actingAs($couple)
        ->postJson('/api/user/media', [
            'url' => $base64Image,
            'is_cover' => true,
        ])
        ->assertCreated();

    $response->assertJsonPath('data.is_cover', true);

    // Verify wedding cover image was updated
    $wedding->refresh();
    expect($wedding->cover_image_url)->not->toBeNull()
        ->and($wedding->media()->count())->toBe(1);
});

test('suspended wedding cannot perform write operations under admin control', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'status' => WeddingStatus::SUSPENDED,
    ]);
    WeddingDetail::create(['wedding_id' => $wedding->id]);

    $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    // Trying to upload media should be forbidden
    $this->actingAs($couple)
        ->postJson('/api/user/media', [
            'url' => $base64Image,
        ])
        ->assertForbidden();

    // Trying to update wedding details should be forbidden
    $this->actingAs($couple)
        ->putJson('/api/user/wedding', [
            'groom_name' => 'New Groom Name',
        ])
        ->assertForbidden();

    // Reading dashboard or media should still be allowed or handled gracefully
    $this->actingAs($couple)
        ->getJson('/api/user/dashboard')
        ->assertOk();
});

test('admin can reactivate suspended wedding allowing couple to resume', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'status' => WeddingStatus::SUSPENDED,
    ]);
    WeddingDetail::create(['wedding_id' => $wedding->id]);

    $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    // Blocked while suspended
    $this->actingAs($couple)
        ->postJson('/api/user/media', ['url' => $base64Image])
        ->assertForbidden();

    // Admin reactivates wedding
    $wedding->update(['status' => WeddingStatus::PUBLISHED]);

    // Now allowed
    $this->actingAs($couple)
        ->postJson('/api/user/media', ['url' => $base64Image])
        ->assertCreated();
});

test('deactivated user is blocked from api', function () {
    $couple = User::factory()->asUser()->create([
        'is_active' => false,
    ]);
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'status' => WeddingStatus::PUBLISHED,
    ]);

    $this->actingAs($couple)
        ->getJson('/api/user/dashboard')
        ->assertForbidden();
});
