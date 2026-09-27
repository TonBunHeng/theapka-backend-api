<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Enables seamless cross-origin communication between theapka-api and
    | both frontends: theapka-user (couple app) and theapka-admin (admin panel).
    | Supports Bearer token authentication and cookie-based credentials.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter([
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:3000',
        env('FRONTEND_USER_URL'),
        env('FRONTEND_ADMIN_URL'),
        ...array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
    ]))),

    'allowed_origins_patterns' => [
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
        '#^https://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization', 'Content-Disposition'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
