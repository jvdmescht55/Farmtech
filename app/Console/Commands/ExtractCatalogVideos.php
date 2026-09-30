<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\VideoUrlExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fills `products.video_url` from a direct video link already present in a
 * product's own stored description HTML / spec text / image payload (see
 * VideoUrlExtractor). Makes NO network calls, never overwrites a URL that's
 * already on file unless --force, and never guesses — "no video found" for
 * most of the catalog is the expected result.
 */
class ExtractCatalogVideos extends Command
{
    protected $signature = 'catalog:extract-videos
        {--status=all : Product status to scan (all, approved, pending_review, ...)}
        {--sku= : Only scan the product with this SKU}
        {--force : Re-scan and overwrite products that already have a video_url}
        {--dry-run : Show what would be filled without writing to the database}';

    protected $description = 'Populate products.video_url from video links already embedded in each product\'s stored description/spec/image data (no network calls).';

    public function handle(VideoUrlExtractor $extractor): int
    {
        $status = (string) $this->option('status');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = Product::query()->with('images');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($sku = $this->option('sku')) {
            $query->where('sku', $sku);
        }

        $products = $query->orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->warn('No products matched the given filters.');

            return self::SUCCESS;
        }

        $this->line(($dryRun ? '<fg=yellow>[DRY RUN] </>' : '')
            ."Scanning {$products->count()} product(s) for embedded video URLs...");

        $rows = [];
        $updated = 0;
        $skippedExisting = 0;

        foreach ($products as $product) {
            if (! $force && trim((string) $product->video_url) !== '') {
                $skippedExisting++;

                continue;
            }

            $url = $extractor->extract($product);

            if ($url === null || $url === $product->video_url) {
                continue;
            }

            if (! $dryRun) {
                $product->forceFill(['video_url' => $url])->save(); // has_video synced by Product::saving hook
            }

            $updated++;
            $rows[] = [
                'SKU' => $product->sku,
                'Product' => Str::limit($product->title, 40),
                'Video URL' => Str::limit($url, 60),
            ];
        }

        $this->newLine();

        if ($rows === []) {
            $this->info('No embedded video URLs were found in stored product data. Nothing to update.');
        } else {
            $this->line('<fg=green;options=bold>'.($dryRun ? 'Would update' : 'Updated')." {$updated} product(s) with a listing video</>");
            $this->table(['SKU', 'Product', 'Video URL'], $rows);
        }

        if ($skippedExisting > 0) {
            $this->line("Skipped {$skippedExisting} product(s) that already have a video_url (pass --force to re-scan).");
        }

        $withVideo = Product::where('has_video', true)->count();
        $this->newLine();
        $this->line("Catalog total with a listing video on file: {$withVideo}/".Product::count());

        if ($dryRun) {
            $this->comment('Dry run — no rows were written. Re-run without --dry-run to persist.');
        }

        return self::SUCCESS;
    }
}
