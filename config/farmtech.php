<?php

return [
    // Fallback defaults used to seed the `settings` table on first migrate.
    // Live values are read from the `settings` table at runtime, editable
    // from /admin/settings — this file only supplies the initial seed.
    'defaults' => [
        'target_margin_pct' => (float) env('DEFAULT_TARGET_MARGIN_PCT', 35),
        'air_freight_usd_per_kg' => (float) env('DEFAULT_AIR_FREIGHT_USD_PER_KG', 16),
        // Same settings key as before, redefined: was a customs "clearing
        // agent fee" (R450), now a flat domestic delivery allowance (R250)
        // per the arbitrage-filter cost model — see LandedCostCalculator.
        'clearing_agent_fee_zar' => (float) env('DEFAULT_CLEARING_AGENT_FEE_ZAR', 250),
        'vat_rate' => (float) env('DEFAULT_VAT_RATE', 0.15),
    ],

    'lead_time_default' => '7-12 business days',

    // Where NewOrderAdminAlertMailable goes. Defaults to the seeded admin
    // login address so there's no extra setup required out of the box.
    // Elvis (?:), not env()'s built-in default: env() only falls back when a
    // key is entirely absent, not when it's present-but-empty (which is what
    // `ADMIN_NOTIFICATION_EMAIL=` in .env actually is) — this treats both the same.
    'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL') ?: (env('ADMIN_EMAIL') ?: 'admin@farmtech.co.za'),
];
