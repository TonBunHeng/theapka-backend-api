<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreSubscriptionRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Subscribe wedding to a paid plan and initiate payment transaction.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $provider = $request->input('provider', 'khqr');

        $result = $this->paymentService->createSubscriptionPayment(
            wedding: $wedding,
            user: $request->user(),
            plan: $plan,
            provider: $provider
        );

        return response()->json([
            'data' => [
                'subscription' => new SubscriptionResource($result['subscription']),
                'payment' => new PaymentResource($result['payment']),
                'provider_payload' => $result['provider_payload'],
            ],
        ], 201);
    }
}
