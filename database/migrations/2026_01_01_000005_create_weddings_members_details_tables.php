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
        Schema::create('weddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->date('wedding_date')->nullable()->index();
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->text('venue_map_url')->nullable();
            $table->string('timezone', 50)->default('Asia/Phnom_Penh');
            $table->string('status', 20)->default('draft')->index();
            $table->string('cover_image_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
        });

        Schema::create('wedding_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('editor');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['wedding_id', 'user_id']);
        });

        Schema::create('wedding_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained('weddings')->cascadeOnDelete();
            $table->string('groom_name')->nullable();
            $table->string('groom_title')->nullable();
            $table->text('groom_parents')->nullable();
            $table->string('bride_name')->nullable();
            $table->string('bride_title')->nullable();
            $table->text('bride_parents')->nullable();
            $table->text('story')->nullable();
            $table->text('welcome_message')->nullable();
            $table->string('dress_code')->nullable();
            $table->json('contact_phones')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wedding_details');
        Schema::dropIfExists('wedding_members');
        Schema::dropIfExists('weddings');
    }
};
