<?php

namespace App\Services\PaymentService\Drivers;

use App\Models\Payment;
use App\Services\PaymentService\Contracts\PaymentDriverInterface;

class AbaPayWayDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('payments.drivers.aba_payway', []);
    }

    public function createPayment(Payment $payment): array
    {
        $merchantId = $this->config['merchant_id'] ?? 'theapka';
        $apiKey = $this->config['api_key'] ?? 'mock_key';
        $apiUrl = $this->config['api_url'] ?? 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase';

        $amount = number_format((float) $payment->amount, 2, '.', '');
        $reqTime = date('YmdHis');
        $hashString = base64_encode(hash_hmac('sha512', $reqTime . $merchantId . $payment->reference . $amount, $apiKey, true));

        $checkoutUrl = "{$apiUrl}?tran_id={$payment->reference}&req_time={$reqTime}&merchant_id={$merchantId}&amount={$amount}&hash={$hashString}";

        return [
            'type' => 'aba_payway',
            'checkout_url' => $checkoutUrl,
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        $apiKey = $this->config['api_key'] ?? '';
        $tranId = $payload['tran_id'] ?? $payload['reference'] ?? '';
        $status = $payload['status'] ?? '';

        $expectedSig = base64_encode(hash_hmac('sha512', $tranId . $status, $apiKey, true));
        return hash_equals($expectedSig, (string) $signature);
    }

    public function processWebhook(array $payload): array
    {
        return [
            'reference' => $payload['tran_id'] ?? $payload['reference'] ?? '',
            'status' => in_array($payload['status'] ?? '', ['0', 'PAID', 'paid', 'approved'], true) ? 'paid' : 'failed',
            'transaction_id' => $payload['apv'] ?? $payload['tran_id'] ?? null,
            'raw' => $payload,
        ];
    }
}
