<?php

return [
    'default' => env('PAYMENT_DEFAULT_DRIVER', 'khqr'),

    'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'USD'),

    'drivers' => [
        'khqr' => [
            'bakong_account_id' => env('KHQR_BAKONG_ACCOUNT_ID', 'bunheng@aclb'),
            'merchant_name' => env('KHQR_MERCHANT_NAME', 'TheapKa Online'),
            'merchant_city' => env('KHQR_MERCHANT_CITY', 'Phnom Penh'),
            'sandbox' => env('KHQR_SANDBOX', true),
        ],

        'aba_payway' => [
            'merchant_id' => env('ABA_PAYWAY_MERCHANT_ID', 'theapka'),
            'api_key' => env('ABA_PAYWAY_API_KEY', ''),
            'api_url' => env('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'),
            'sandbox' => env('ABA_PAYWAY_SANDBOX', true),
        ],
    ],
];
