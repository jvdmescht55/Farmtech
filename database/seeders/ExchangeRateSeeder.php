<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Placeholder rate — the worker's forex.js overwrites this with a live
        // rate on first pipeline run, and falls back to this row if offline.
        ExchangeRate::store('USDZAR', 18.50);
    }
}
