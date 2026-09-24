<?php

namespace Database\Factories;

use App\Models\GuestGroup;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestGroupFactory extends Factory
{
    protected $model = GuestGroup::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->randomElement(['VIP Guests', 'Family & Relatives', 'High School Friends', 'Colleagues']),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
