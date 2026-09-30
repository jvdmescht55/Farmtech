<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Adopts ONE human-verified match: copies the real reference data from an
 * FT-ALI listing onto an FT-BT research candidate, while keeping everything
 * we curated.
 *
 *   Copied from the FT-ALI source : source_url, supplier_name, supplier_url,
 *                                   the image gallery, and the raw
 *                                   specifications block.
 *   Kept from the FT-BT target    : title, slug, short description,
 *                                   key features, "What's included",
 *                                   the "Target Pain Point" / "OEM /
 *                                   Demo-Video Search" spec highlights, and
 *                                   all costing — USD cost, weight, duty,
 *                                   landed cost and the ZAR retail price.
 *
 * The candidate stays status=pending_review / is_active=false — this does
 * NOT publish it. An admin reviews the photos and toggles it to approved.
 * verification_tier becomes 'linked_candidate'. Idempotent: re-running
 * re-syncs the gallery and specs.
 */
class LinkBoerTechCandidate extends Command
{
    protected $signature = 'catalog:link-boer-tech
        {target : FT-BT-* candidate SKU to enrich}
        {source : FT-ALI-* listing SKU to copy reference data from}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Copy source_url / supplier / images / raw specs from a verified FT-ALI listing onto an FT-BT candidate, keeping our title, highlights and ZAR pricing. Stays pending_review.';

    /** Curated spec keys on the candidate that must survive the raw-spec copy. */
    private const KEEP_SPEC_KEYS = ['Target Pain Point', 'OEM / Demo-Video Search', 'Sourcing Batch'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $target = Product::where('sku', $this->argument('target'))->first();
        $source = Product::with('images')->where('sku', $this->argument('source'))->first();

        if (! $target) {
            $this->error("Target {$this->argument('target')} not found.");

            return self::FAILURE;
        }

        if (! $source) {
            $this->error("Source {$this->argument('source')} not found.");

            return self::FAILURE;
        }

        if (! str_starts_with($target->sku, 'FT-BT-')) {
            $this->error("Target {$target->sku} is not an FT-BT- candidate.");

            return self::FAILURE;
        }

        if (! str_starts_with($source->sku, 'FT-ALI-')) {
            $this->error("Source {$source->sku} is not an FT-ALI- listing.");

            return self::FAILURE;
        }

        $keptSpecs = array_intersect_key(
            (array) $target->specifications,
            array_flip(self::KEEP_SPEC_KEYS),
        );

        $mergedSpecs = array_merge(
            (array) $source->specifications,
            $keptSpecs,
            [
                'Reference Listing' => $source->sku.' — '.$source->title,
                'Listing Status' => 'Specs, photos and supplier copied from reference Alibaba listing '.$source->sku
                    .'. Factory price, MOQ, DDP air rate and final specification still to be confirmed with the supplier before publishing.',
            ],
        );

        $this->line("<options=bold>Linking {$target->sku}  ←  {$source->sku}</>");
        $this->table(['Field', 'Before (candidate)', 'After (from source)'], [
            ['title', $this->short($target->title), '(unchanged)'],
            ['retail_price_zar', 'R'.number_format((float) $target->retail_price_zar, 2), '(unchanged)'],
            ['profit_margin_pct', $target->profit_margin_pct.'%', '(unchanged)'],
            ['supplier_name', $target->supplier_name ?: '—', $source->supplier_name ?: '—'],
            ['supplier_url', $this->short($target->supplier_url ?: '—'), $this->short($source->supplier_url ?: '—')],
            ['source_url', $this->short($target->source_url ?: '—'), $this->short($source->source_url ?: '—')],
            ['images', (string) $target->images()->count(), (string) $source->images->count()],
            ['specifications keys', (string) count((array) $target->specifications), (string) count($mergedSpecs)],
            ['verification_tier', $target->verification_tier ?: '—', 'linked_candidate'],
            ['status', $target->status, '(unchanged — still pending_review)'],
        ]);

        if ($source->images->isEmpty()) {
            $this->warn('Source listing has no images — the candidate will have no gallery.');
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing written.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($target, $source, $mergedSpecs) {
            $target->forceFill([
                'source_url' => $source->source_url,
                'supplier_name' => $source->supplier_name,
                'supplier_url' => $source->supplier_url,
                'specifications' => $mergedSpecs,
                'verification_tier' => 'linked_candidate',
            ])->save();

            $target->images()->delete();

            foreach ($source->images as $image) {
                ProductImage::create([
                    'product_id' => $target->id,
                    'original_url' => $image->original_url,
                    'local_path' => $image->local_path,
                    'is_thumbnail' => $image->is_thumbnail,
                    'sort_order' => $image->sort_order,
                ]);
            }
        });

        $target->refresh();

        $this->info("Linked. {$target->sku} now has {$target->images()->count()} image(s), supplier "
            .'"'.($target->supplier_name ?: '—').'", and a real source_url. Still pending_review.');
        $this->line('Review & approve: '.route('admin.products.show', $target));

        return self::SUCCESS;
    }

    private function short(?string $value, int $limit = 48): string
    {
        $value = (string) $value;

        return strlen($value) > $limit ? substr($value, 0, $limit - 1).'…' : $value;
    }
}
