<?php

namespace Database\Factories;

use App\Enums\GiftCurrency;
use App\Enums\GiftEntryType;
use App\Models\GiftRecord;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GiftRecordFactory extends Factory
{
    protected $model = GiftRecord::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'client_uuid' => (string) Str::uuid(),
            'guest_id' => null,
            'giver_name' => fake()->name(),
            'amount' => fake()->randomElement([50.00, 100.00, 200.00]),
            'currency' => GiftCurrency::USD,
            'method' => 'cash',
            'entry_type' => GiftEntryType::GIFT,
            'corrects_id' => null,
            'notes' => null,
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
            'created_at' => now(),
        ];
    }
}
