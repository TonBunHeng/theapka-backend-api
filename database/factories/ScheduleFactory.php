<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'title' => fake()->randomElement(['ពិធីហែកំណត់', 'ពិធីសំពះផ្ទឹម', 'ពិធីកាត់សក់', 'ពិធីពិសាភោជនាហារ']),
            'description' => 'ពិធីតាមប្រពៃណីខ្មែរ',
            'start_time' => '07:00 AM',
            'end_time' => '09:00 AM',
            'location' => 'Main Ballroom',
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
