<?php

// Herd Manager subscription. One price per farm, every device included.
// Change in .env, then run `php artisan config:cache`.
return [
    'monthly_cents' => (int) env('BILLING_MONTHLY_CENTS', 24900),   // R249 a month
    'yearly_cents' => (int) env('BILLING_YEARLY_CENTS', 249000),    // R2 490 a year (two months free)
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 90),            // free from the day the first device is activated
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 14),            // full access while a payment is on its way
    'remind_days' => [7, 1],                                        // emails this many days before access ends
];
