<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use App\Services\LandedCostCalculator;
use Illuminate\Console\Command;

class RecalculateProductPricing extends Command
{
    protected $signature = 'products:recalculate-pricing {--sku= : Only recalculate the product with this SKU}';
    protected $description = 'Re-derives landed cost, retail price, and margin for existing products through LandedCostCalculator, using each product\'s own stored supplier cost/weight/exchange rate and the current Settings.';

    public function handle(): int
    {
        $query = Product::query();

        if ($sku = $this->option('sku')) {
            $query->where('sku', $sku);
        }

        $products = $query->get();
        $total = $products->count();
        $this->info("Recalculating pricing for {$total} product(s)...");

        // LandedCostCalculator takes its freight/delivery/VAT settings via
        // constructor args (no service-container binding exists for it —
        // same pattern SourcingPipelineRunner's Node counterpart and the
        // pre-existing admin controller used), so build it here from the
        // live admin-configurable Settings.
        $calculator = new LandedCostCalculator(
            freightUsdPerKg: (float) Setting::get('air_freight_usd_per_kg', 16),
            domesticDeliveryZar: (float) Setting::get('clearing_agent_fee_zar', 250),
            vatRate: (float) Setting::get('vat_rate', 0.15),
        );

        $targetMarginPct = (float) Setting::get('target_margin_pct', 35);

        foreach ($products as $product) {
            $weightKg = (float) ($product->gross_weight_kg ?: $product->est_weight_kg ?: 0.5);
            $exchangeRate = (float) ($product->exchange_rate ?: 18.50);
            $dutyRate = (float) ($product->customs_duty_rate ?: 0.10);
            $marginPct = (float) ($product->profit_margin_pct ?: $targetMarginPct);

            $breakdown = $calculator->calculate(
                (float) $product->original_price_usd,
                $weightKg,
                $exchangeRate,
                $dutyRate,
                $marginPct,
            );

            $product->update([
                'intl_freight_zar' => $breakdown['intl_freight_zar'],
                'customs_vat_zar' => $breakdown['customs_vat_zar'],
                'domestic_delivery_zar' => $breakdown['domestic_delivery_zar'],
                'landed_cost_zar' => $breakdown['landed_cost_zar'],
                'retail_price_zar' => $breakdown['retail_price_zar'],
                'profit_margin_pct' => $marginPct,
            ]);

            $this->line("  #{$product->id} {$product->sku}: landed R{$breakdown['landed_cost_zar']} -> retail R{$breakdown['retail_price_zar']}");
        }

        $this->info('Done.');

        return 0;
    }
}
