<?php

use App\Http\Controllers\Api\Admin\AnnouncementController;
use App\Http\Controllers\Api\Admin\ContentController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\GuestController;
use App\Http\Controllers\Api\Admin\InvitationController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\SupportController;
use App\Http\Controllers\Api\Admin\TemplateController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\WeddingController;
use App\Http\Controllers\Api\User\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin,super_admin', 'log.admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('can:dashboard.view');

    // Users
    Route::get('/users', [UserController::class, 'index'])->middleware('can:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('can:users.create');
    Route::get('/users/{id}', [UserController::class, 'show'])->middleware('can:users.view');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('can:users.edit');
    Route::post('/users/{id}/suspend', [UserController::class, 'suspend'])->middleware('can:users.suspend');
    Route::post('/users/{id}/reactivate', [UserController::class, 'reactivate'])->middleware('can:users.edit');
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->middleware('can:users.edit');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('can:users.delete');

    // Weddings
    Route::get('/weddings', [WeddingController::class, 'index'])->middleware('can:weddings.view');
    Route::get('/weddings/{id}', [WeddingController::class, 'show'])->middleware('can:weddings.view');
    Route::post('/weddings/{id}/suspend', [WeddingController::class, 'suspend'])->middleware('can:weddings.suspend');
    Route::post('/weddings/{id}/restore', [WeddingController::class, 'restore'])->middleware('can:weddings.edit');
    Route::post('/weddings/{id}/archive', [WeddingController::class, 'archive'])->middleware('can:weddings.edit');
    Route::delete('/weddings/{id}', [WeddingController::class, 'destroy'])->middleware('can:weddings.delete');
    Route::get('/weddings/{id}/guests', [WeddingController::class, 'guests']);
    Route::get('/weddings/{id}/gifts-summary', [WeddingController::class, 'giftsSummary']);

    // Invitations Moderation
    Route::get('/invitations', [InvitationController::class, 'index'])->middleware('can:invitations.view');
    Route::post('/invitations/{id}/unpublish', [InvitationController::class, 'unpublish'])->middleware('can:invitations.moderate');
    Route::post('/invitations/{id}/flag', [InvitationController::class, 'flag'])->middleware('can:invitations.moderate');

    // Templates
    Route::get('/templates', [TemplateController::class, 'index'])->middleware('can:templates.view');
    Route::post('/templates', [TemplateController::class, 'store'])->middleware('can:templates.create');
    Route::get('/templates/{id}', [TemplateController::class, 'show'])->middleware('can:templates.view');
    Route::put('/templates/{id}', [TemplateController::class, 'update'])->middleware('can:templates.edit');
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->middleware('can:templates.delete');
    Route::post('/templates/{id}/publish', [TemplateController::class, 'publish'])->middleware('can:templates.publish');
    Route::post('/templates/{id}/retire', [TemplateController::class, 'retire'])->middleware('can:templates.edit');

    // Content
    Route::get('/content', [ContentController::class, 'index'])->middleware('can:content.view');
    Route::get('/content/{key}', [ContentController::class, 'show'])->middleware('can:content.view');
    Route::put('/content/{key}', [ContentController::class, 'update'])->middleware('can:content.edit');

    // Cross-wedding guests (requires guests.view)
    Route::get('/guests', [GuestController::class, 'index']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('can:payments.view');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])->middleware('can:payments.view');
    Route::post('/payments/{id}/verify', [PaymentController::class, 'verify'])->middleware('can:payments.verify');
    Route::post('/payments/{id}/refund', [PaymentController::class, 'refund'])->middleware('can:payments.refund');

    // Reports
    Route::get('/reports/{type}', [ReportController::class, 'show'])->middleware('can:reports.view');
    Route::get('/reports/{type}/export', [ReportController::class, 'export'])->middleware('can:reports.export');

    // Support
    Route::get('/support', [SupportController::class, 'index'])->middleware('can:support.view');
    Route::get('/support/{id}', [SupportController::class, 'show'])->middleware('can:support.view');
    Route::post('/support/{id}/reply', [SupportController::class, 'reply'])->middleware('can:support.reply');
    Route::post('/support/{id}/assign', [SupportController::class, 'assign'])->middleware('can:support.assign');
    Route::post('/support/{id}/close', [SupportController::class, 'close'])->middleware('can:support.close');

    // Media
    Route::get('/media', [MediaController::class, 'index'])->middleware('can:media.view');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->middleware('can:media.delete');

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('can:announcements.view');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('can:announcements.create');
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->middleware('can:announcements.view');
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->middleware('can:announcements.edit');
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->middleware('can:announcements.delete');

    // Admin self profile and password
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'updatePassword']);
});
