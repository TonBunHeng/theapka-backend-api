<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin cannot view a super admin account in user detail', function () {
    $admin = User::factory()->asAdmin()->create();
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($admin)
        ->getJson("/api/admin/users/{$superAdmin->id}")
        ->assertStatus(403);
});

test('admin user list excludes super admin accounts', function () {
    $admin = User::factory()->asAdmin()->create(['email' => 'admin@theapka.com']);
    $couple = User::factory()->asUser()->create(['email' => 'couple@theapka.com']);
    $superAdmin = User::factory()->asSuperAdmin()->create(['email' => 'boss@theapka.com']);

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/users')
        ->assertOk();

    $emails = collect($response->json('data'))->pluck('email')->all();

    expect($emails)->toContain('couple@theapka.com')
        ->and($emails)->not->toContain('boss@theapka.com');
});

test('admin cannot update, suspend, or delete a super admin account', function () {
    $admin = User::factory()->asAdmin()->create();
    $superAdmin = User::factory()->asSuperAdmin()->create();

    // Attempt update
    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$superAdmin->id}", [
            'name' => 'Hacked Name',
        ])
        ->assertStatus(403);

    // Attempt suspend
    $this->actingAs($admin)
        ->postJson("/api/admin/users/{$superAdmin->id}/suspend")
        ->assertStatus(403);

    // Attempt delete
    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$superAdmin->id}")
        ->assertStatus(403);

    // Super Admin remains active and intact
    expect($superAdmin->fresh()->is_active)->toBeTrue();
    expect($superAdmin->fresh()->deleted_at)->toBeNull();
});

test('admin cannot access super admin endpoints', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->getJson('/api/super-admin/admins')
        ->assertStatus(403);

    $this->actingAs($admin)
        ->getJson('/api/super-admin/roles')
        ->assertStatus(403);

    $this->actingAs($admin)
        ->getJson('/api/super-admin/settings')
        ->assertStatus(403);

    $this->actingAs($admin)
        ->getJson('/api/super-admin/audit-logs')
        ->assertStatus(403);
});

test('self-lockout protection: cannot disable the last active super admin', function () {
    // Exactly one active super admin
    $soleSuperAdmin = User::factory()->asSuperAdmin()->create(['is_active' => true]);

    $this->actingAs($soleSuperAdmin)
        ->postJson("/api/super-admin/admins/{$soleSuperAdmin->id}/disable")
        ->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Action rejected. You cannot disable the last active Super Admin account.',
        ]);

    expect($soleSuperAdmin->fresh()->is_active)->toBeTrue();
});

test('self-lockout protection: cannot demote the last active super admin', function () {
    $soleSuperAdmin = User::factory()->asSuperAdmin()->create(['is_active' => true]);

    $this->actingAs($soleSuperAdmin)
        ->putJson("/api/super-admin/admins/{$soleSuperAdmin->id}", [
            'role' => RoleName::ADMIN->value,
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Action rejected. You cannot demote the last active Super Admin account.',
        ]);

    expect($soleSuperAdmin->fresh()->hasRole(RoleName::SUPER_ADMIN->value))->toBeTrue();
});

test('super admin can disable another super admin if multiple active exist', function () {
    $superAdmin1 = User::factory()->asSuperAdmin()->create(['is_active' => true]);
    $superAdmin2 = User::factory()->asSuperAdmin()->create(['is_active' => true]);

    $this->actingAs($superAdmin1)
        ->postJson("/api/super-admin/admins/{$superAdmin2->id}/disable")
        ->assertOk();

    expect($superAdmin2->fresh()->is_active)->toBeFalse();
});

test('super admin cannot assign super_admin exclusive permissions to the admin role', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->putJson('/api/super-admin/roles', [
            'role' => RoleName::ADMIN->value,
            'permissions' => [
                'dashboard.view',
                'users.view',
                'roles.edit', // Super Admin ONLY
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['permissions']);

    $adminRole = Role::findByName(RoleName::ADMIN->value, 'web');
    expect($adminRole->hasPermissionTo('roles.edit'))->toBeFalse();
});

test('super admin can grant sensitive couple permissions to admin role', function () {
    $superAdmin = User::factory()->asSuperAdmin()->create();

    $this->actingAs($superAdmin)
        ->putJson('/api/super-admin/roles', [
            'role' => RoleName::ADMIN->value,
            'permissions' => [
                'dashboard.view',
                'users.view',
                'guests.view',
                'gifts.view',
            ],
        ])
        ->assertOk();

    $adminRole = Role::findByName(RoleName::ADMIN->value, 'web');
    expect($adminRole->hasPermissionTo('guests.view'))->toBeTrue()
        ->and($adminRole->hasPermissionTo('gifts.view'))->toBeTrue();
});
