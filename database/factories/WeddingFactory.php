<?php

namespace Database\Factories;

use App\Enums\WeddingStatus;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WeddingFactory extends Factory
{
    protected $model = Wedding::class;

    public function definition(): array
    {
        $title = fake()->name() . ' & ' . fake()->name() . ' Wedding';
        return [
            'owner_id' => User::factory(),
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'title' => $title,
            'wedding_date' => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'venue_name' => 'The Premier Centre Sen Sok',
            'venue_address' => 'St 1003, Phnom Penh, Cambodia',
            'venue_map_url' => 'https://maps.google.com/?q=sen+sok',
            'timezone' => 'Asia/Phnom_Penh',
            'status' => WeddingStatus::PUBLISHED,
            'cover_image_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552',
            'settings' => [
                'enable_rsvp' => true,
                'enable_wishes' => true,
                'enable_gift_registry' => true,
                'moderate_wishes' => false,
            ],
        ];
    }
}
