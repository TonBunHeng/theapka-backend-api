<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->json('features')->nullable();
            $table->integer('max_guests')->default(500);
            $table->integer('max_photos')->default(50);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('thumbnail_url')->nullable();
            $table->string('preview_url')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_premium')->default(false)->index();
            $table->string('status', 20)->default('published')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
        Schema::dropIfExists('plans');
    }
};
