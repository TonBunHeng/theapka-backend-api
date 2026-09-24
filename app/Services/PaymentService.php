<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PaymentService\Contracts\PaymentDriverInterface;
use App\Services\PaymentService\Drivers\AbaPayWayDriver;
use App\Services\PaymentService\Drivers\KhqrDriver;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PaymentService
{
    /**
     * Resolve a payment driver by provider key.
     */
    public function driver(?string $provider = null): PaymentDriverInterface
    {
        $provider = $provider ?? config('payments.default', 'khqr');

        return match ($provider) {
            'khqr' => new KhqrDriver(),
            'aba_payway' => new AbaPayWayDriver(),
            default => throw new InvalidArgumentException("Unsupported payment driver: {$provider}"),
        };
    }

    /**
     * Create a subscription and initiate pending payment.
     *
     * @param  \App\Models\Wedding  $wedding
     * @param  \App\Models\User  $user
     * @param  \App\Models\Plan  $plan
     * @param  string  $provider
     * @return array<string, mixed>
     */
    public function createSubscriptionPayment(
        Wedding $wedding,
        User $user,
        Plan $plan,
        string $provider = 'khqr'
    ): array {
        // Create pending subscription
        $subscription = Subscription::create([
            'wedding_id' => $wedding->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        // Generate unique reference
        $reference = 'PAY-' . strtoupper(Str::random(12));

        // Create pending payment
        $payment = Payment::create([
            'wedding_id' => $wedding->id,
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'reference' => $reference,
            'provider' => $provider,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'status' => PaymentStatus::PENDING,
            'raw_payload' => null,
        ]);

        // If free plan, activate immediately
        if ($plan->price <= 0) {
            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);

            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]);

            return [
                'subscription' => $subscription,
                'payment' => $payment,
                'provider_payload' => [
                    'type' => 'free',
                    'message' => 'Subscription activated automatically for free plan.',
                ],
            ];
        }

        // Generate provider payment payload
        $driver = $this->driver($provider);
        $providerPayload = $driver->createPayment($payment);

        return [
            'subscription' => $subscription,
            'payment' => $payment,
            'provider_payload' => $providerPayload,
        ];
    }

    /**
     * Process webhook from a payment provider.
     *
     * @param  string  $provider
     * @param  array<string, mixed>  $payload
     * @param  string|null  $signature
     * @return array{success: bool, payment: \App\Models\Payment|null, message: string}
     */
    public function handleWebhook(string $provider, array $payload, ?string $signature = null): array
    {
        $driver = $this->driver($provider);

        if (! $driver->verifyWebhook($payload, $signature)) {
            return [
                'success' => false,
                'payment' => null,
                'message' => 'Invalid webhook signature.',
            ];
        }

        $result = $driver->processWebhook($payload);
        $payment = Payment::where('reference', $result['reference'])->first();

        if (! $payment) {
            return [
                'success' => false,
                'payment' => null,
                'message' => 'Payment reference not found.',
            ];
        }

        if ($result['status'] === 'paid') {
            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
                'raw_payload' => $result['raw'],
            ]);

            // Activate subscription
            if ($payment->subscription_id) {
                $payment->subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                ]);
            }

            return [
                'success' => true,
                'payment' => $payment,
                'message' => 'Payment confirmed successfully.',
            ];
        }

        $payment->update([
            'status' => PaymentStatus::FAILED,
            'raw_payload' => $result['raw'],
        ]);

        return [
            'success' => true,
            'payment' => $payment,
            'message' => 'Payment marked as failed.',
        ];
    }

    /**
     * Admin manual verification of a payment.
     */
    public function verifyManualPayment(Payment $payment, User $admin): Payment
    {
        $payment->update([
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'verified_by' => $admin->id,
        ]);

        if ($payment->subscription_id && $payment->subscription) {
            $payment->subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]);
        }

        return $payment;
    }

    /**
     * Refund a payment.
     */
    public function refund(Payment $payment, string $reason): Payment
    {
        if ($payment->status === PaymentStatus::REFUNDED) {
            throw new RuntimeException('Payment is already refunded.');
        }

        $payment->update([
            'status' => PaymentStatus::REFUNDED,
            'refund_reason' => $reason,
        ]);

        if ($payment->subscription_id && $payment->subscription) {
            $payment->subscription->update([
                'status' => 'cancelled',
            ]);
        }

        return $payment;
    }
}
