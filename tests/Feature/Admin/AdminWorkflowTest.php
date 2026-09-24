<?php

use App\Enums\RoleName;
use App\Enums\WeddingStatus;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\SupportTicket;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin cannot view wedding guests without guests.view permission, but can with permission and it is audited', function () {
    // Admin without guests.view
    $admin = User::factory()->asAdmin()->create();
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    Guest::create([
        'wedding_id' => $wedding->id,
        'name' => 'Secret Guest',
        'seats' => 2,
    ]);

    // Forbidden without guests.view
    $this->actingAs($admin)
        ->getJson("/api/admin/weddings/{$wedding->id}/guests")
        ->assertStatus(403);

    // Grant guests.view to admin
    $admin->givePermissionTo('guests.view');

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/weddings/{$wedding->id}/guests")
        ->assertOk();

    expect(count($response->json('data')))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Secret Guest');

    // Verify audit log was recorded for accessing private couple data
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'guests.view',
        'subject_id' => $wedding->id,
    ]);
});

test('admin cannot view wedding gifts summary without gifts.view permission, but can with permission and it is audited', function () {
    $admin = User::factory()->asAdmin()->create();
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);

    // Forbidden without gifts.view
    $this->actingAs($admin)
        ->getJson("/api/admin/weddings/{$wedding->id}/gifts-summary")
        ->assertStatus(403);

    // Grant gifts.view
    $admin->givePermissionTo('gifts.view');

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/weddings/{$wedding->id}/gifts-summary")
        ->assertOk();

    expect($response->json('data'))->toHaveKeys(['KHR', 'USD']);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'gifts.view',
        'subject_id' => $wedding->id,
    ]);
});

test('admin write action triggers LogAdminAction middleware and creates audit log', function () {
    $admin = User::factory()->asAdmin()->create();
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create([
        'owner_id' => $couple->id,
        'status' => WeddingStatus::PUBLISHED,
    ]);

    AuditLog::query()->delete();

    // Admin suspends wedding
    $this->actingAs($admin)
        ->postJson("/api/admin/weddings/{$wedding->id}/suspend")
        ->assertOk();

    expect($wedding->fresh()->status)->toBe(WeddingStatus::SUSPENDED);

    // Exactly one audit log produced
    $auditLogs = AuditLog::where('actor_id', $admin->id)->get();
    expect($auditLogs->count())->toBe(1);

    $log = $auditLogs->first();
    expect($log->action)->toBe('weddings.suspend')
        ->and($log->actor_id)->toBe($admin->id);
});

test('admin can manage templates publication state', function () {
    $admin = User::factory()->asAdmin()->create();
    $template = Template::factory()->create(['status' => 'draft']);

    // Publish template
    $this->actingAs($admin)
        ->postJson("/api/admin/templates/{$template->id}/publish")
        ->assertOk();

    expect($template->fresh()->status)->toBe('published');

    // Retire template
    $this->actingAs($admin)
        ->postJson("/api/admin/templates/{$template->id}/retire")
        ->assertOk();

    expect($template->fresh()->status)->toBe('retired');
});

test('admin can manage announcements CRUD', function () {
    $admin = User::factory()->asAdmin()->create();

    // Create announcement
    $createRes = $this->actingAs($admin)
        ->postJson('/api/admin/announcements', [
            'title' => 'Scheduled Maintenance Notice',
            'content' => 'System maintenance scheduled for Sunday at 2 AM.',
            'target_role' => 'user',
            'starts_at' => now()->toDateTimeString(),
            'is_active' => true,
        ])->assertCreated();

    $announcementId = $createRes->json('data.id');

    // Update announcement
    $this->actingAs($admin)
        ->putJson("/api/admin/announcements/{$announcementId}", [
            'title' => 'Updated Maintenance Notice',
        ])->assertOk()
          ->assertJsonPath('data.title', 'Updated Maintenance Notice');

    // Delete announcement
    $this->actingAs($admin)
        ->deleteJson("/api/admin/announcements/{$announcementId}")
        ->assertOk();

    expect(Announcement::find($announcementId))->toBeNull();
});

test('admin can reply, assign, and close support tickets', function () {
    $admin = User::factory()->asAdmin()->create();
    $couple = User::factory()->asUser()->create();

    $ticket = SupportTicket::create([
        'user_id' => $couple->id,
        'subject' => 'Payment issue',
        'status' => 'open',
        'priority' => 'high',
    ]);

    // Reply
    $this->actingAs($admin)
        ->postJson("/api/admin/support/{$ticket->id}/reply", [
            'message' => 'We are investigating your payment transaction.',
        ])->assertOk();

    expect($ticket->fresh()->status)->toBe('in_progress');

    // Assign
    $this->actingAs($admin)
        ->postJson("/api/admin/support/{$ticket->id}/assign", [
            'assigned_to' => $admin->id,
        ])->assertOk();

    expect($ticket->fresh()->assigned_to)->toBe($admin->id);

    // Close
    $this->actingAs($admin)
        ->postJson("/api/admin/support/{$ticket->id}/close")
        ->assertOk();

    expect($ticket->fresh()->status)->toBe('closed');
});
