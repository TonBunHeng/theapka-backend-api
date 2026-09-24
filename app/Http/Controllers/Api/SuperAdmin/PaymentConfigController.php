<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\TestPaymentConfigRequest;
use App\Http\Requests\SuperAdmin\UpdatePaymentConfigRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PaymentConfigController extends Controller
{
    /**
     * Show payment configurations (secrets masked/never returned in plaintext).
     */
    public function show(): JsonResponse
    {
        $khqr = Setting::get('payment_khqr', config('payments.drivers.khqr', []));
        $aba = Setting::get('payment_aba_payway', config('payments.drivers.aba_payway', []));

        $khqrData = [
            'enabled' => (bool) ($khqr['enabled'] ?? true),
            'merchant_id' => $khqr['merchant_id'] ?? $khqr['bakong_account_id'] ?? '',
            'merchant_name' => $khqr['merchant_name'] ?? 'TheapKa',
            'merchant_city' => $khqr['merchant_city'] ?? 'Phnom Penh',
            'bakong_account_id' => $khqr['bakong_account_id'] ?? '',
            'environment' => ($khqr['sandbox'] ?? true) ? 'sandbox' : 'production',
            'sandbox' => (bool) ($khqr['sandbox'] ?? true),
        ];

        $paywayData = [
            'enabled' => (bool) ($aba['enabled'] ?? true),
            'merchant_id' => $aba['merchant_id'] ?? '',
            'merchant_name' => $aba['merchant_name'] ?? 'TheapKa Online',
            'environment' => ($aba['sandbox'] ?? true) ? 'sandbox' : 'production',
            'api_url' => $aba['api_url'] ?? '',
            'api_key_configured' => ! empty($aba['api_key']),
            'sandbox' => (bool) ($aba['sandbox'] ?? true),
        ];

        return response()->json([
            'data' => [
                'khqr' => $khqrData,
                'payway' => $paywayData,
                'aba_payway' => $paywayData,
            ],
        ]);
    }

    /**
     * Update payment configurations.
     */
    public function update(UpdatePaymentConfigRequest $request): JsonResponse
    {
        if ($request->has('khqr')) {
            $existing = Setting::get('payment_khqr', []);
            $merged = array_merge($existing, $request->validated('khqr'));
            Setting::set('payment_khqr', $merged, 'payment', false);
        }

        if ($request->has('aba_payway')) {
            $existing = Setting::get('payment_aba_payway', []);
            $newAba = $request->validated('aba_payway');

            // Preserve existing key if empty string sent
            if (empty($newAba['api_key']) && ! empty($existing['api_key'])) {
                $newAba['api_key'] = $existing['api_key'];
            }

            $merged = array_merge($existing, $newAba);
            Setting::set('payment_aba_payway', $merged, 'payment', false);
        }

        return $this->show();
    }

    /**
     * Test payment provider integration.
     */
    public function test(TestPaymentConfigRequest $request): JsonResponse
    {
        $provider = $request->provider;

        // Perform connection / credential health test
        if ($provider === 'khqr') {
            $khqr = Setting::get('payment_khqr', config('payments.drivers.khqr', []));
            $isConfigured = ! empty($khqr['bakong_account_id']);

            return response()->json([
                'data' => [
                    'provider' => 'khqr',
                    'success' => $isConfigured,
                    'message' => $isConfigured ? 'KHQR configuration validated.' : 'Missing Bakong account ID.',
                ],
            ]);
        }

        $aba = Setting::get('payment_aba_payway', config('payments.drivers.aba_payway', []));
        $isConfigured = ! empty($aba['merchant_id']) && ! empty($aba['api_key']);

        return response()->json([
            'data' => [
                'provider' => 'aba_payway',
                'success' => $isConfigured,
                'message' => $isConfigured ? 'ABA PayWay credentials configured.' : 'Missing ABA merchant ID or API key.',
            ],
        ]);
    }
}
