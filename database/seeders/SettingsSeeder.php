<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('farmtech.defaults');

        Setting::set('target_margin_pct', $defaults['target_margin_pct'], 'decimal', 'Default Target Margin (%)');
        Setting::set('air_freight_usd_per_kg', $defaults['air_freight_usd_per_kg'], 'decimal', 'Air Freight Rate (USD/kg)');
        Setting::set('clearing_agent_fee_zar', $defaults['clearing_agent_fee_zar'], 'decimal', 'Clearing Agent Flat Fee (ZAR)');
        Setting::set('vat_rate', $defaults['vat_rate'], 'decimal', 'SARS VAT Rate');
    }
}
