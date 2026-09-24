<?php

use App\Models\Checkin;
use App\Models\GiftRecord;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Invitation;
use App\Models\Media;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Wedding;
use App\Models\Wish;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('couple A cannot query couple B wedding or any scoped child resource', function () {
    $coupleA = User::factory()->asUser()->create(['name' => 'Couple A']);
    $coupleB = User::factory()->asUser()->create(['name' => 'Couple B']);

    $weddingA = Wedding::factory()->create(['owner_id' => $coupleA->id]);
    $weddingB = Wedding::factory()->create(['owner_id' => $coupleB->id]);

    // Create resources for Couple A
    $scheduleA = Schedule::factory()->create(['wedding_id' => $weddingA->id]);
    $groupA = GuestGroup::factory()->create(['wedding_id' => $weddingA->id]);
    $guestA = Guest::factory()->create(['wedding_id' => $weddingA->id]);
    $invitationA = Invitation::factory()->create(['wedding_id' => $weddingA->id]);
    $wishA = Wish::create([
        'wedding_id' => $weddingA->id,
        'sender_name' => 'Friend A',
        'message' => 'Congratulations A!',
    ]);
    $checkinA = Checkin::create([
        'wedding_id' => $weddingA->id,
        'guest_id' => $guestA->id,
        'schedule_id' => $scheduleA->id,
        'checked_in_at' => now(),
    ]);
    $giftA = GiftRecord::factory()->create([
        'wedding_id' => $weddingA->id,
        'recorded_by' => $coupleA->id,
    ]);
    $mediaA = Media::create([
        'wedding_id' => $weddingA->id,
        'uploaded_by' => $coupleA->id,
        'disk' => 'public',
        'file_path' => 'photos/a.jpg',
        'file_name' => 'a.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
    ]);

    // Create resources for Couple B
    $scheduleB = Schedule::factory()->create(['wedding_id' => $weddingB->id]);
    $groupB = GuestGroup::factory()->create(['wedding_id' => $weddingB->id]);
    $guestB = Guest::factory()->create(['wedding_id' => $weddingB->id]);
    $invitationB = Invitation::factory()->create(['wedding_id' => $weddingB->id]);
    $wishB = Wish::create([
        'wedding_id' => $weddingB->id,
        'sender_name' => 'Friend B',
        'message' => 'Congratulations B!',
    ]);
    $checkinB = Checkin::create([
        'wedding_id' => $weddingB->id,
        'guest_id' => $guestB->id,
        'schedule_id' => $scheduleB->id,
        'checked_in_at' => now(),
    ]);
    $giftB = GiftRecord::factory()->create([
        'wedding_id' => $weddingB->id,
        'recorded_by' => $coupleB->id,
    ]);
    $mediaB = Media::create([
        'wedding_id' => $weddingB->id,
        'uploaded_by' => $coupleB->id,
        'disk' => 'public',
        'file_path' => 'photos/b.jpg',
        'file_name' => 'b.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
    ]);

    // Act as Couple A
    $this->actingAs($coupleA);

    // 1. Wedding isolation
    expect(Wedding::pluck('id')->all())->toBe([$weddingA->id])
        ->and(Wedding::find($weddingB->id))->toBeNull();

    // 2. Schedule isolation
    expect(Schedule::pluck('id')->all())->toBe([$scheduleA->id])
        ->and(Schedule::find($scheduleB->id))->toBeNull();

    // 3. GuestGroup isolation
    expect(GuestGroup::pluck('id')->all())->toBe([$groupA->id])
        ->and(GuestGroup::find($groupB->id))->toBeNull();

    // 4. Guest isolation
    expect(Guest::pluck('id')->all())->toBe([$guestA->id])
        ->and(Guest::find($guestB->id))->toBeNull();

    // 5. Invitation isolation
    expect(Invitation::pluck('id')->all())->toBe([$invitationA->id])
        ->and(Invitation::find($invitationB->id))->toBeNull();

    // 6. Wish isolation
    expect(Wish::pluck('id')->all())->toBe([$wishA->id])
        ->and(Wish::find($wishB->id))->toBeNull();

    // 7. Checkin isolation
    expect(Checkin::pluck('id')->all())->toBe([$checkinA->id])
        ->and(Checkin::find($checkinB->id))->toBeNull();

    // 8. GiftRecord isolation
    expect(GiftRecord::pluck('id')->all())->toBe([$giftA->id])
        ->and(GiftRecord::find($giftB->id))->toBeNull();

    // 9. Media isolation
    expect(Media::pluck('id')->all())->toBe([$mediaA->id])
        ->and(Media::find($mediaB->id))->toBeNull();
});

test('admin and super admin are not constrained by WeddingScope', function () {
    $coupleA = User::factory()->asUser()->create();
    $coupleB = User::factory()->asUser()->create();

    $weddingA = Wedding::factory()->create(['owner_id' => $coupleA->id]);
    $weddingB = Wedding::factory()->create(['owner_id' => $coupleB->id]);

    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    // Admin should see both weddings
    $weddings = Wedding::pluck('id')->all();
    expect($weddings)->toContain($weddingA->id, $weddingB->id);

    $superAdmin = User::factory()->asSuperAdmin()->create();
    $this->actingAs($superAdmin);

    // Super Admin should see both weddings
    $superWeddings = Wedding::pluck('id')->all();
    expect($superWeddings)->toContain($weddingA->id, $weddingB->id);
});
