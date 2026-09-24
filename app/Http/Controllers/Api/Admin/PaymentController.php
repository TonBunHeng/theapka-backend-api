<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundPaymentRequest;
use App\Http\Requests\Admin\VerifyPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * List all payments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['wedding', 'subscription.plan', 'user']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $filters = $request->query('filter', []);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }
        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $payments = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => PaymentResource::collection($payments->items()),
            'meta' => [
                'page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Show payment details (raw_payload secrets are hidden).
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with(['wedding', 'subscription.plan', 'user', 'verifier'])->findOrFail($id);

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Verify and confirm a manual payment.
     */
    public function verify(VerifyPaymentRequest $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        $this->paymentService->verifyManualPayment($payment, $request->user());
        $payment->load(['wedding', 'subscription.plan', 'user', 'verifier']);

        return response()->json([
            'data' => [
                'message' => "Payment {$payment->reference} verified successfully.",
                'payment' => new PaymentResource($payment),
            ],
        ]);
    }

    /**
     * Refund a payment with reason.
     */
    public function refund(RefundPaymentRequest $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        $this->paymentService->refund($payment, $request->reason);
        $payment->load(['wedding', 'subscription.plan', 'user', 'verifier']);

        return response()->json([
            'data' => [
                'message' => "Payment {$payment->reference} refunded.",
                'payment' => new PaymentResource($payment),
            ],
        ]);
    }
}
