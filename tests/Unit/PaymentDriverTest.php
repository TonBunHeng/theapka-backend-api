<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PaymentService;
use App\Services\PaymentService\Drivers\AbaPayWayDriver;
use App\Services\PaymentService\Drivers\KhqrDriver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('payment service resolves correct drivers', function () {
    $service = new PaymentService();

    expect($service->driver('khqr'))->toBeInstanceOf(KhqrDriver::class)
        ->and($service->driver('aba_payway'))->toBeInstanceOf(AbaPayWayDriver::class);

    expect(fn () => $service->driver('unsupported_gateway'))
        ->toThrow(InvalidArgumentException::class);
});

test('khqr driver generates valid payment payload with qr string', function () {
    $driver = new KhqrDriver();

    $payment = new Payment([
        'reference' => 'TEST-REF-12345',
        'amount' => 25.00,
        'currency' => 'USD',
    ]);

    $payload = $driver->createPayment($payment);

    expect($payload['type'])->toBe('khqr')
        ->and($payload['reference'])->toBe('TEST-REF-12345')
        ->and($payload['qr_string'])->not->toBeEmpty()
        ->and($payload['qr_image'])->not->toBeEmpty();
});

test('manual verification marks payment paid and activates subscription', function () {
    $admin = User::factory()->asAdmin()->create();
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);
    $plan = Plan::factory()->create(['price' => 50.00]);

    $subscription = Subscription::create([
        'wedding_id' => $wedding->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'wedding_id' => $wedding->id,
        'subscription_id' => $subscription->id,
        'user_id' => $couple->id,
        'reference' => 'PAY-MANUAL-VERIFY',
        'provider' => 'khqr',
        'amount' => 50.00,
        'currency' => 'USD',
        'status' => PaymentStatus::PENDING,
    ]);

    $service = new PaymentService();
    $verifiedPayment = $service->verifyManualPayment($payment, $admin);

    expect($verifiedPayment->status)->toBe(PaymentStatus::PAID)
        ->and($verifiedPayment->verified_by)->toBe($admin->id)
        ->and($subscription->fresh()->status)->toBe('active')
        ->and($subscription->fresh()->starts_at)->not->toBeNull();
});

test('refund updates status with reason and cancels subscription without deleting payment row', function () {
    $couple = User::factory()->asUser()->create();
    $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);
    $plan = Plan::factory()->create(['price' => 50.00]);

    $subscription = Subscription::create([
        'wedding_id' => $wedding->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addYear(),
    ]);

    $payment = Payment::create([
        'wedding_id' => $wedding->id,
        'subscription_id' => $subscription->id,
        'user_id' => $couple->id,
        'reference' => 'PAY-REFUND-TEST',
        'provider' => 'khqr',
        'amount' => 50.00,
        'currency' => 'USD',
        'status' => PaymentStatus::PAID,
    ]);

    $service = new PaymentService();
    $refundedPayment = $service->refund($payment, 'Customer requested cancellation within 24 hours');

    expect($refundedPayment->status)->toBe(PaymentStatus::REFUNDED)
        ->and($refundedPayment->refund_reason)->toBe('Customer requested cancellation within 24 hours')
        ->and($subscription->fresh()->status)->toBe('cancelled');

    // Row is preserved in database
    expect(Payment::where('reference', 'PAY-REFUND-TEST')->exists())->toBeTrue();
});
