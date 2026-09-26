<?php

use App\Http\Controllers\Api\User\DashboardController;
use App\Http\Controllers\Api\User\GiftController;
use App\Http\Controllers\Api\User\GuestController;
use App\Http\Controllers\Api\User\GuestGroupController;
use App\Http\Controllers\Api\User\GuestImportController;
use App\Http\Controllers\Api\User\InvitationController;
use App\Http\Controllers\Api\User\MediaController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\QrCodeController;
use App\Http\Controllers\Api\User\ScheduleController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\WeddingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:user', 'wedding.not_suspended'])->prefix('user')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Wedding profile
    Route::get('/wedding', [WeddingController::class, 'show']);
    Route::post('/wedding', [WeddingController::class, 'store']);
    Route::put('/wedding', [WeddingController::class, 'update']);

    // Schedules
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
    Route::put('/schedules/{id}', [ScheduleController::class, 'update']);
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);

    // Guest Groups
    Route::get('/guest-groups', [GuestGroupController::class, 'index']);
    Route::post('/guest-groups', [GuestGroupController::class, 'store']);
    Route::get('/guest-groups/{id}', [GuestGroupController::class, 'show']);
    Route::put('/guest-groups/{id}', [GuestGroupController::class, 'update']);
    Route::delete('/guest-groups/{id}', [GuestGroupController::class, 'destroy']);

    // Guests
    Route::get('/guests', [GuestController::class, 'index']);
    Route::post('/guests', [GuestController::class, 'store']);
    Route::get('/guests/{id}', [GuestController::class, 'show']);
    Route::put('/guests/{id}', [GuestController::class, 'update']);
    Route::delete('/guests/{id}', [GuestController::class, 'destroy']);

    // Guest Import
    Route::post('/guests/import', [GuestImportController::class, 'import']);
    Route::post('/guests/import/preview', [GuestImportController::class, 'preview']);
    Route::post('/guests/import/commit', [GuestImportController::class, 'commit']);

    // Invitation
    Route::get('/invitation', [InvitationController::class, 'show']);
    Route::put('/invitation', [InvitationController::class, 'update']);
    Route::post('/invitation/publish', [InvitationController::class, 'publish']);
    Route::post('/invitation/unpublish', [InvitationController::class, 'unpublish']);

    // QR Codes
    Route::get('/qr-codes/guest/{id}', [QrCodeController::class, 'forGuest']);
    Route::get('/qr-codes/wedding', [QrCodeController::class, 'forWedding']);
    Route::get('/qr-code/guest/{id}', [QrCodeController::class, 'forGuest']);
    Route::get('/qr-code/wedding', [QrCodeController::class, 'forWedding']);

    // Gift Ledger (STRICT: GET and POST only. Absolutely NO PUT/PATCH/DELETE)
    Route::get('/gifts', [GiftController::class, 'index']);
    Route::post('/gifts', [GiftController::class, 'store']);
    Route::get('/gifts/summary', [GiftController::class, 'summary']);

    // Media
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);

    // Subscriptions
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);

    // Profile & Password
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'updatePassword']);
});
