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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->nullable()->constrained('weddings')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('disk', 30)->default('public');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->json('dimensions')->nullable();
            $table->string('type', 30)->default('image');
            $table->string('collection', 50)->default('gallery')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('wedding_id');
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('start_time', 20);
            $table->string('end_time', 20)->nullable();
            $table->string('location')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('wedding_id');
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained('weddings')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('custom_css')->nullable();
            $table->json('content')->nullable();
            $table->string('music_url')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_moderation_enabled')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('media');
    }
};
