<?php

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeddingDetailFactory extends Factory
{
    protected $model = WeddingDetail::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'groom_name' => fake()->name('male'),
            'groom_title' => 'លោក',
            'groom_parents' => 'លោកឪពុក & អ្នកម្តាយខាងប្រុស',
            'bride_name' => fake()->name('female'),
            'bride_title' => 'កញ្ញា',
            'bride_parents' => 'លោកឪពុក & អ្នកម្តាយខាងស្រី',
            'story' => 'Our love story started in Phnom Penh...',
            'welcome_message' => 'សូមគោរពអញ្ជើញភ្ញៀវកិត្តិយសទាំងអស់',
            'dress_code' => 'Traditional Khmer Formal / Western Formal',
            'contact_phones' => ['+85512000001', '+85512000002'],
            'custom_fields' => [],
        ];
    }
}
