<?php

// Farmtech shop settings. Fill these in .env, then run `php artisan config:cache`.
return [
    // Courier fee in cents. Leave empty to confirm delivery cost with each order.
    'courier_cents' => env('SHOP_COURIER_CENTS') !== null && env('SHOP_COURIER_CENTS') !== '' ? (int) env('SHOP_COURIER_CENTS') : null,
    'free_courier_over_cents' => env('SHOP_FREE_COURIER_OVER_CENTS') ? (int) env('SHOP_FREE_COURIER_OVER_CENTS') : null,
    'collect_from' => env('SHOP_COLLECT_FROM', ''),   // e.g. "Kenhardt, Northern Cape — we'll arrange a time"

    // EFT banking details shown after an order. Empty = "we'll send them with your invoice".
    'bank' => [
        'name' => env('SHOP_BANK_NAME', ''),
        'account_name' => env('SHOP_BANK_ACCOUNT_NAME', ''),
        'account_number' => env('SHOP_BANK_ACCOUNT_NUMBER', ''),
        'branch_code' => env('SHOP_BANK_BRANCH_CODE', ''),
    ],
];
