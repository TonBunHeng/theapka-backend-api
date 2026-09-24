<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Khmer Traditional / បែបប្រពៃណីខ្មែរ',
                'slug' => 'khmer-traditional',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&auto=format&fit=crop',
                'preview_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&auto=format&fit=crop',
                'config' => [
                    'theme' => 'traditional',
                    'primary_color' => '#8B0000',
                    'accent_color' => '#D4AF37',
                    'font_family' => 'Kantumruy Pro, sans-serif',
                ],
                'is_active' => true,
                'is_premium' => false,
                'status' => 'published',
            ],
            [
                'name' => 'Modern Luxury Gold / មាសទំនើប',
                'slug' => 'modern-gold',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=600&auto=format&fit=crop',
                'preview_url' => 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=1200&auto=format&fit=crop',
                'config' => [
                    'theme' => 'luxury',
                    'primary_color' => '#1A1A1A',
                    'accent_color' => '#FFD700',
                    'font_family' => 'Playfair Display, serif',
                ],
                'is_active' => true,
                'is_premium' => true,
                'status' => 'published',
            ],
            [
                'name' => 'Minimalist Floral / ផ្កាស្រស់',
                'slug' => 'minimal-floral',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?w=600&auto=format&fit=crop',
                'preview_url' => 'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?w=1200&auto=format&fit=crop',
                'config' => [
                    'theme' => 'floral',
                    'primary_color' => '#4A5568',
                    'accent_color' => '#E2B1B1',
                    'font_family' => 'Outfit, sans-serif',
                ],
                'is_active' => true,
                'is_premium' => false,
                'status' => 'published',
            ],
            [
                'name' => 'Royal Angkor / រាជវាំងអង្គរ',
                'slug' => 'royal-angkor',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1532712938310-34cb3982ef74?w=600&auto=format&fit=crop',
                'preview_url' => 'https://images.unsplash.com/photo-1532712938310-34cb3982ef74?w=1200&auto=format&fit=crop',
                'config' => [
                    'theme' => 'royal',
                    'primary_color' => '#1E3A8A',
                    'accent_color' => '#F59E0B',
                    'font_family' => 'Cinzel, serif',
                ],
                'is_active' => true,
                'is_premium' => true,
                'status' => 'published',
            ],
        ];

        foreach ($templates as $template) {
            Template::firstOrCreate(['slug' => $template['slug']], $template);
        }
    }
}
