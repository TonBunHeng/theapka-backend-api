<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'subscription_id' => Subscription::factory(),
            'user_id' => User::factory(),
            'reference' => 'PAY-' . strtoupper(Str::random(12)),
            'provider' => 'khqr',
            'amount' => 29.00,
            'currency' => 'USD',
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'raw_payload' => ['txn_id' => Str::random(16)],
            'refund_reason' => null,
            'verified_by' => null,
        ];
    }
}
