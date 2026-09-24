<?php

use App\Http\Controllers\Api\Public\InvitationViewController;
use App\Http\Controllers\Api\Public\PaymentWebhookController;
use App\Http\Controllers\Api\Public\RsvpController;
use App\Http\Controllers\Api\Public\WishController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->middleware('throttle:60,1')->group(function () {
    // Wishes listing must precede {token?} route to prevent matching 'wishes' as a token
    Route::get('/invitation/{slug}/wishes', [WishController::class, 'index']);
    Route::get('/invitation/{slug}/{token?}', [InvitationViewController::class, 'show']);

    // RSVP & Wishes
    Route::post('/invitation/{token}/rsvp', [RsvpController::class, 'store'])->middleware('throttle:20,1');
    Route::post('/invitation/{token}/wish', [WishController::class, 'store'])->middleware('throttle:20,1');

    // Webhooks
    Route::post('/payments/webhook/{provider}', [PaymentWebhookController::class, 'handle']);
});
