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
        Schema::create('gift_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->string('client_uuid', 64)->unique();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->string('giver_name');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD'); // KHR, USD
            $table->string('method', 30)->default('cash'); // cash, aba_qr, bank_transfer
            $table->string('entry_type', 20)->default('gift'); // gift, correction
            $table->foreignId('corrects_id')->nullable()->references('id')->on('gift_records')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wedding_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_records');
    }
};
