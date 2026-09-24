<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Super admin exclusive permissions that can NEVER be assigned to admin.
     *
     * @var array<string>
     */
    public const SUPER_ADMIN_ONLY_PERMISSIONS = [
        'admins.view',
        'admins.create',
        'admins.edit',
        'admins.disable',
        'roles.view',
        'roles.edit',
        'settings.view',
        'settings.edit',
        'security.view',
        'security.edit',
        'audit.view',
        'payment_config.view',
        'payment_config.edit',
        'backup.view',
        'backup.create',
        'backup.restore',
        'maintenance.toggle',
    ];

    /**
     * Permissions reserved from default admin (can be optionally granted to specific admins by super admin).
     *
     * @var array<string>
     */
    public const SENSITIVE_COUPLE_PERMISSIONS = [
        'guests.view',
        'guests.export',
        'gifts.view',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = [
            'dashboard.view',
            'users.view', 'users.create', 'users.edit', 'users.suspend', 'users.delete',
            'weddings.view', 'weddings.edit', 'weddings.suspend', 'weddings.delete',
            'invitations.view', 'invitations.moderate',
            'templates.view', 'templates.create', 'templates.edit', 'templates.publish', 'templates.delete',
            'content.view', 'content.edit',
            'guests.view', 'guests.export',
            'gifts.view',
            'payments.view', 'payments.verify', 'payments.refund',
            'reports.view', 'reports.export',
            'support.view', 'support.reply', 'support.assign', 'support.close',
            'media.view', 'media.delete',
            'announcements.view', 'announcements.create', 'announcements.edit', 'announcements.delete',

            // super_admin only
            'admins.view', 'admins.create', 'admins.edit', 'admins.disable',
            'roles.view', 'roles.edit',
            'settings.view', 'settings.edit',
            'security.view', 'security.edit',
            'audit.view',
            'payment_config.view', 'payment_config.edit',
            'backup.view', 'backup.create', 'backup.restore',
            'maintenance.toggle',
        ];

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles
        $superAdminRole = Role::firstOrCreate(['name' => RoleName::SUPER_ADMIN->value, 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => RoleName::USER->value, 'guard_name' => 'web']);

        // Super admin gets ALL permissions
        $superAdminRole->syncPermissions(Permission::all());

        // Admin gets default subset (excludes super_admin only and sensitive couple data)
        $adminPermissions = array_diff($allPermissions, self::SUPER_ADMIN_ONLY_PERMISSIONS, self::SENSITIVE_COUPLE_PERMISSIONS);
        $adminRole->syncPermissions($adminPermissions);

        // User role gets empty direct permissions (actions are governed by ownership and policies)
        $userRole->syncPermissions([]);
    }
}
