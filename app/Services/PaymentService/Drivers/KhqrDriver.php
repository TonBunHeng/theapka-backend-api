<?php

namespace App\Services\PaymentService\Drivers;

use App\Models\Payment;
use App\Services\PaymentService\Contracts\PaymentDriverInterface;
use App\Services\QrCodeService;

class KhqrDriver implements PaymentDriverInterface
{
    protected array $config;
    protected QrCodeService $qrCodeService;

    public function __construct(?array $config = null, ?QrCodeService $qrCodeService = null)
    {
        $this->config = $config ?? config('payments.drivers.khqr', []);
        $this->qrCodeService = $qrCodeService ?? new QrCodeService();
    }

    public function createPayment(Payment $payment): array
    {
        $bakongId = $this->config['bakong_account_id'] ?? 'theapka@aclb';
        $merchantName = $this->config['merchant_name'] ?? 'TheapKa Online';
        $merchantCity = $this->config['merchant_city'] ?? 'Phnom Penh';

        // Format a standard KHQR mock string for payment reference
        $currencyCode = $payment->currency === 'KHR' ? '116' : '840';
        $formattedAmount = number_format((float) $payment->amount, 2, '.', '');

        $khqrPayload = sprintf(
            '00020101021229300010%s0108%s520459995303%s540%s%s5802KH59%s%s60%s%s62%s01%s%s6304',
            strlen($bakongId),
            $bakongId,
            $currencyCode,
            strlen($formattedAmount),
            $formattedAmount,
            strlen($merchantName),
            $merchantName,
            strlen($merchantCity),
            $merchantCity,
            strlen($payment->reference) + 4,
            strlen($payment->reference),
            $payment->reference
        );

        $qrDataUri = $this->qrCodeService->generateDataUri($khqrPayload, 320);

        return [
            'type' => 'khqr',
            'qr_string' => $khqrPayload,
            'qr_image' => $qrDataUri,
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null): bool
    {
        // For development/mocking, if reference exists and matches hash or testing mode
        if (app()->environment('local', 'testing')) {
            return true;
        }

        $expectedSig = hash_hmac('sha256', json_encode($payload), $this->config['bakong_account_id'] ?? 'secret');
        return hash_equals($expectedSig, (string) $signature);
    }

    public function processWebhook(array $payload): array
    {
        return [
            'reference' => $payload['reference'] ?? $payload['tran_id'] ?? '',
            'status' => in_array($payload['status'] ?? '', ['SUCCESS', 'PAID', 'paid'], true) ? 'paid' : 'failed',
            'transaction_id' => $payload['transaction_id'] ?? $payload['hash'] ?? null,
            'raw' => $payload,
        ];
    }
}
