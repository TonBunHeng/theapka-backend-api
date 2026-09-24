<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for TheapKa Online
|--------------------------------------------------------------------------
|
| Load modular API route definitions:
| - auth: Sanctum authentication and profile me
| - user: Couple wedding, guest, schedule, invitation, gift ledger
| - admin: Platform management, moderation, payments, reports, support
| - super_admin: Admin accounts, roles matrix, settings, security, audit, backups
| - public: Guests public invitation page, RSVP, wishes, webhooks
|
*/

require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/user.php';
require __DIR__ . '/api/admin.php';
require __DIR__ . '/api/super_admin.php';
require __DIR__ . '/api/public.php';

Route::get('/docs.yaml', function () {
    return response()->file(base_path('openapi.yaml'), [
        'Content-Type' => 'text/yaml; charset=utf-8',
    ]);
});
