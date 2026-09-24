<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free / ឥតគិតថ្លៃ',
                'slug' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'features' => [
                    'digital_invitation' => true,
                    'rsvp' => true,
                    'wishes' => true,
                    'gift_ledger' => true,
                    'qr_code' => true,
                    'custom_music' => false,
                    'photo_gallery' => true,
                ],
                'max_guests' => 100,
                'max_photos' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Standard / ស្តង់ដារ',
                'slug' => 'standard',
                'price' => 29.00,
                'currency' => 'USD',
                'features' => [
                    'digital_invitation' => true,
                    'rsvp' => true,
                    'wishes' => true,
                    'gift_ledger' => true,
                    'qr_code' => true,
                    'custom_music' => true,
                    'photo_gallery' => true,
                    'guest_import' => true,
                    'export_reports' => true,
                ],
                'max_guests' => 500,
                'max_photos' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Premium / ពិសេស',
                'slug' => 'premium',
                'price' => 59.00,
                'currency' => 'USD',
                'features' => [
                    'digital_invitation' => true,
                    'rsvp' => true,
                    'wishes' => true,
                    'gift_ledger' => true,
                    'qr_code' => true,
                    'custom_music' => true,
                    'photo_gallery' => true,
                    'guest_import' => true,
                    'export_reports' => true,
                    'custom_domain' => true,
                    'vip_support' => true,
                ],
                'max_guests' => 2000,
                'max_photos' => 200,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
