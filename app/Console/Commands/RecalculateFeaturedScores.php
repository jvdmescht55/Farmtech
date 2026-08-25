<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Storefront curation algorithm — recomputes Product::computeFeaturedScore()
 * for every storefront-visible product from real signals (margin, weight/
 * compactness, photo/description richness, reviews, verified-supplier
 * trust, units sold, click-throughs — see that method's docblock) and sets
 * is_featured=true on the top {--limit=12}, false on everyone else.
 *
 * Safe/idempotent to re-run on a schedule (routes/console.php) as reviews,
 * sales, and click-throughs accumulate — never a one-time hand-picked list.
 */
class RecalculateFeaturedScores extends Command
{
    protected $signature = 'products:rank-featured {--limit=12 : How many top-scoring products to mark is_featured}';
    protected $description = 'Recomputes the real-signal featured_score for every storefront-visible product and flags the top scorers as is_featured.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $products = Product::storefrontVisible()
            ->with(['images', 'reviews', 'complianceAudit'])
            ->withSum('orderItems as units_sold', 'quantity')
            ->get();

        $this->info("Scoring {$products->count()} storefront-visible product(s)...");

        foreach ($products as $product) {
            $product->featured_score = $product->computeFeaturedScore();
        }

        $ranked = $products->sortByDesc('featured_score')->values();
        $featuredIds = $ranked->take($limit)->pluck('id')->all();

        foreach ($ranked as $product) {
            $product->is_featured = in_array($product->id, $featuredIds, true);
            $product->save();
        }

        $this->newLine();
        $this->info("Featured (top {$limit}):");
        foreach ($ranked->take($limit) as $product) {
            $this->line(sprintf('  %6.2f  [%s] %s', $product->featured_score, $product->sku, $product->title));
        }

        return 0;
    }
}
