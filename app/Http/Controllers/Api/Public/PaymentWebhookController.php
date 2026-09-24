<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Handle payment provider webhook callback (signature verified).
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $signature = $request->header('X-Signature') ?? $request->input('hash') ?? $request->header('hash');
        $payload = $request->all();

        $result = $this->paymentService->handleWebhook(
            provider: $provider,
            payload: $payload,
            signature: $signature
        );

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'data' => [
                'message' => $result['message'],
                'reference' => $result['payment']?->reference,
                'status' => ($result['payment'] && $result['payment']->status instanceof \App\Enums\PaymentStatus)
                    ? $result['payment']->status->value
                    : (string) ($result['payment']?->status ?? ''),
            ],
        ]);
    }
}
