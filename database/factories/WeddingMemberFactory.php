<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeddingMemberFactory extends Factory
{
    protected $model = WeddingMember::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'user_id' => User::factory(),
            'role' => 'editor',
            'joined_at' => now(),
        ];
    }
}
