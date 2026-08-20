<?php

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    // Shared secret for POST /api/pipeline/webhook — scheduled scrapers
    // (Apify etc.) send this back in the X-Pipeline-Secret header. Not set
    // by default, so the endpoint fails closed until an operator opts in.
    'pipeline_webhook' => [
        'secret' => env('PIPELINE_WEBHOOK_SECRET'),
    ],

    'exchange_rate' => [
        'api_key' => env('USD_ZAR_API_KEY'),
        'provider_url' => env('USD_ZAR_API_URL', 'https://v6.exchangerate-api.com/v6'),
    ],

    'payfast' => [
        'merchant_id' => env('PAYFAST_MERCHANT_ID'),
        'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
        'passphrase' => env('PAYFAST_PASSPHRASE'),
        'sandbox' => env('PAYFAST_SANDBOX', true),
    ],

    'ozow' => [
        'site_code' => env('OZOW_SITE_CODE'),
        'private_key' => env('OZOW_PRIVATE_KEY'),
        'api_key' => env('OZOW_API_KEY'),
        'sandbox' => env('OZOW_SANDBOX', true),
    ],

    'yoco' => [
        'public_key' => env('YOCO_PUBLIC_KEY'),
        'secret_key' => env('YOCO_SECRET_KEY'),
    ],

    'courier_guy' => [
        'api_key' => env('COURIER_GUY_API_KEY'),
        'account_number' => env('COURIER_GUY_ACCOUNT_NUMBER'),
    ],

    'dhl' => [
        'api_key' => env('DHL_API_KEY'),
        'account_number' => env('DHL_ACCOUNT_NUMBER'),
    ],
];
