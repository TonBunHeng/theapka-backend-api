<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->word() . ' Plan';
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'price' => 29.00,
            'currency' => 'USD',
            'features' => ['rsvp' => true, 'gallery' => true],
            'max_guests' => 500,
            'max_photos' => 50,
            'is_active' => true,
        ];
    }
}
