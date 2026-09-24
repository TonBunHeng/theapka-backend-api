<?php

namespace App\Services\PaymentService\Contracts;

use App\Models\Payment;

interface PaymentDriverInterface
{
    /**
     * Initiate payment transaction and return provider payload.
     *
     * @param  \App\Models\Payment  $payment
     * @return array<string, mixed>
     */
    public function createPayment(Payment $payment): array;

    /**
     * Verify authenticity of a webhook or callback payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  string|null  $signature
     * @return bool
     */
    public function verifyWebhook(array $payload, ?string $signature = null): bool;

    /**
     * Extract transaction details from webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{reference: string, status: string, transaction_id: string|null, raw: array<string, mixed>}
     */
    public function processWebhook(array $payload): array;
}
