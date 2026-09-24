<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'template_id' => Template::factory(),
            'slug' => Str::random(10),
            'title' => 'Official Wedding Invitation',
            'custom_css' => null,
            'content' => [
                'hero_title' => 'Save Our Date',
            ],
            'music_url' => null,
            'view_count' => 0,
            'status' => 'published',
            'is_moderation_enabled' => false,
            'published_at' => now(),
        ];
    }
}
