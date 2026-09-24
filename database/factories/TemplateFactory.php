<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        $name = fake()->word() . ' Template';
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'thumbnail_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552',
            'preview_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552',
            'config' => [
                'theme' => 'default',
                'primary_color' => '#8B0000',
            ],
            'is_active' => true,
            'is_premium' => false,
            'status' => 'published',
        ];
    }
}
