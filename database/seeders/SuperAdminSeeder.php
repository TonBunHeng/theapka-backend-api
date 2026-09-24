<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@theapka.com'],
            [
                'name' => 'TheapKa Super Admin',
                'phone' => '+85512000001',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles([RoleName::SUPER_ADMIN->value]);

        // 2. Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@theapka.com'],
            [
                'name' => 'TheapKa Admin',
                'phone' => '+85512000002',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([RoleName::ADMIN->value]);

        // 3. Couple / User
        $user = User::firstOrCreate(
            ['email' => 'couple@theapka.com'],
            [
                'name' => 'Sokha & Bopha',
                'phone' => '+85512000003',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles([RoleName::USER->value]);
    }
}
