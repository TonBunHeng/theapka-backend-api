<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'group_id' => null,
            'name' => fake()->name(),
            'phone' => fake()->numerify('+85512######'),
            'email' => fake()->safeEmail(),
            'side' => fake()->randomElement(['groom', 'bride', 'mutual']),
            'seats' => fake()->numberBetween(1, 4),
            'token' => Str::random(32),
            'notes' => null,
        ];
    }
}
