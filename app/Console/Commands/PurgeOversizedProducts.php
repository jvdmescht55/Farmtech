<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ValueDensityEvaluator;
use Illuminate\Console\Command;

/**
 * Automated Catalog Purge — the Logistics & Dimensional Gatekeeper applied
 * retroactively across the existing catalog (the scraper-side integration in
 * worker/stage_all_to_review.php / worker/src/pipeline.js only stops NEW
 * items from ever being inserted; this sweeps what's already in the DB from
 * before that gate existed, or from ingestion paths that bypass it).
 *
 * Never touches a product that's already status=rejected with a
 * rejection_reason recorded by a previous run of this same command, so
 * re-running it is idempotent and cheap. Does not touch products an admin
 * has manually rejected for an unrelated reason either way — it only ever
 * moves a product INTO rejected, never back out of it.
 */
class PurgeOversizedProducts extends Command
{
    protected $signature = 'catalog:purge-oversized {--dry-run : Report what would be rejected without writing any changes}';
    protected $description = 'Scans all products against the Logistics & Dimensional Gatekeeper (max weight/dimensions, hazardous goods, value density) and auto-rejects non-viable imports.';

    public function handle(ValueDensityEvaluator $evaluator): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $products = Product::query()
            ->where(function ($query) {
                $query->where('status', '!=', 'rejected')->orWhereNull('rejection_reason');
            })
            ->get();

        $this->info('Scanning ' . $products->count() . ' product(s) against the Logistics & Dimensional Gatekeeper' . ($dryRun ? ' [DRY RUN]' : '') . '...');

        $rejectedCounts = [];
        $rejectedTotal = 0;

        foreach ($products as $product) {
            $hazardText = $product->title . ' ' . implode(' ', $product->specifications ?? []) . ' ' . implode(' ', $product->key_features ?? []);

            $result = $evaluator->evaluateLogistics([
                'gross_weight_kg' => $product->gross_weight_kg !== null ? (float) $product->gross_weight_kg : null,
                'package_dimensions' => $product->package_dimensions,
                'hazard_text' => $hazardText,
                'intl_freight_zar' => $product->intl_freight_zar !== null ? (float) $product->intl_freight_zar : null,
                'retail_price_zar' => $product->retail_price_zar !== null ? (float) $product->retail_price_zar : null,
            ]);

            if ($result['passes']) {
                continue;
            }

            $rejectedTotal++;
            $rejectedCounts[$result['reason']] = ($rejectedCounts[$result['reason']] ?? 0) + 1;

            $this->line("  REJECT [{$product->sku}] {$product->title} — {$result['detail']}");

            if (!$dryRun) {
                $product->status = 'rejected';
                $product->is_active = false;
                $product->rejection_reason = $result['detail'];
                $product->save();
            }
        }

        $activeCount = Product::storefrontVisible()->count();
        $rejectedCatalogCount = Product::where('status', 'rejected')->count();

        $this->newLine();
        $this->info('Done. ' . ($dryRun ? 'Would reject' : 'Rejected') . " {$rejectedTotal} product(s) this run:");
        foreach ($rejectedCounts as $reason => $count) {
            $this->line("  - {$reason}: {$count}");
        }

        $this->newLine();
        $this->info('Catalog summary:');
        $this->line("  Active/storefront-visible: {$activeCount}");
        $this->line("  Rejected (total, all-time): {$rejectedCatalogCount}");

        return 0;
    }
}
