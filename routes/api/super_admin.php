<?php

use App\Http\Controllers\Api\SuperAdmin\AdminAccountController;
use App\Http\Controllers\Api\SuperAdmin\AuditLogController;
use App\Http\Controllers\Api\SuperAdmin\BackupController;
use App\Http\Controllers\Api\SuperAdmin\MaintenanceController;
use App\Http\Controllers\Api\SuperAdmin\PaymentConfigController;
use App\Http\Controllers\Api\SuperAdmin\RolePermissionController;
use App\Http\Controllers\Api\SuperAdmin\SecurityController;
use App\Http\Controllers\Api\SuperAdmin\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:super_admin', 'log.admin'])->prefix('super-admin')->group(function () {
    // Admin account management
    Route::get('/admins', [AdminAccountController::class, 'index']);
    Route::post('/admins', [AdminAccountController::class, 'store']);
    Route::get('/admins/{id}', [AdminAccountController::class, 'show']);
    Route::put('/admins/{id}', [AdminAccountController::class, 'update']);
    Route::post('/admins/{id}/disable', [AdminAccountController::class, 'disable']);
    Route::post('/admins/{id}/enable', [AdminAccountController::class, 'enable']);
    Route::post('/admins/{id}/force-reset', [AdminAccountController::class, 'forceReset']);

    // Roles and permissions matrix
    Route::get('/roles', [RolePermissionController::class, 'index']);
    Route::put('/roles', [RolePermissionController::class, 'update']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

    // Security
    Route::get('/security', [SecurityController::class, 'show']);
    Route::put('/security', [SecurityController::class, 'update']);
    Route::get('/sessions', [SecurityController::class, 'sessions']);
    Route::delete('/sessions/{id}', [SecurityController::class, 'destroySession']);
    Route::delete('/security/sessions/{id}', [SecurityController::class, 'destroySession']);

    // Audit logs (Read only - strictly no update or delete routes)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/export', [AuditLogController::class, 'export']);

    // Payment configuration
    Route::get('/payment-config', [PaymentConfigController::class, 'show']);
    Route::put('/payment-config', [PaymentConfigController::class, 'update']);
    Route::post('/payment-config/test', [PaymentConfigController::class, 'test']);

    // Backups
    Route::get('/backups', [BackupController::class, 'index']);
    Route::post('/backups', [BackupController::class, 'store']);
    Route::post('/backups/{id}/restore', [BackupController::class, 'restore']);
    Route::get('/backups/{id}/download', [BackupController::class, 'download']);

    // Maintenance
    Route::get('/maintenance', [MaintenanceController::class, 'show']);
    Route::put('/maintenance', [MaintenanceController::class, 'update']);
});
