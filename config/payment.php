<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    */

    'default' => env('PAYMENT_GATEWAY', 'paypal'),

    /*
    |--------------------------------------------------------------------------
    | Payment Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'sandbox' => [
            'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
            'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        ],
        'live' => [
            'client_id' => env('PAYPAL_LIVE_CLIENT_ID'),
            'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET'),
        ],
        'settings' => [
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled' => true,
            'log.FileName' => storage_path('logs/paypal.log'),
            'log.LogLevel' => 'INFO',
        ],
        'return_url' => env('PAYPAL_RETURN_URL', env('APP_URL') . '/payment/success'),
        'cancel_url' => env('PAYPAL_CANCEL_URL', env('APP_URL') . '/payment/cancel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('PAYMENT_CURRENCY', 'usd'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Fees
    |--------------------------------------------------------------------------
    */

    'fees' => [
        'paypal' => [
            'percentage' => 2.9, // 2.9%
            'fixed' => 0.30, // $0.30
        ],
        'stripe' => [
            'percentage' => 2.9, // 2.9%
            'fixed' => 0.30, // $0.30
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Refund Policy (days)
    |--------------------------------------------------------------------------
    */

    'refund_period' => 30, // 30 days

];