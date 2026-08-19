<?php

return [
    // Fallback defaults used to seed the `settings` table on first migrate.
    // Live values are read from the `settings` table at runtime, editable
    // from /admin/settings — this file only supplies the initial seed.
    'defaults' => [
        'target_margin_pct' => (float) env('DEFAULT_TARGET_MARGIN_PCT', 35),
        'air_freight_usd_per_kg' => (float) env('DEFAULT_AIR_FREIGHT_USD_PER_KG', 9.5),
        'clearing_agent_fee_zar' => (float) env('DEFAULT_CLEARING_AGENT_FEE_ZAR', 450),
        'vat_rate' => (float) env('DEFAULT_VAT_RATE', 0.15),
    ],

    'lead_time_default' => '7-12 business days',
];
