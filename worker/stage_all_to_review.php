<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

require __DIR__ . '/lib_pricing.php';

// Optional CLI filters: --sku=FT-ALI-XXXXXXXX or --limit=N (matched against
// the SKU this script itself would compute for each item, so callers can
// verify a single known listing without re-ingesting everything).
// --skip-images updates every DB field except photos, without the network
// round-trips — for re-applying a text-only change (title cleanup, pricing
// fix, etc.) across the whole catalog fast, without re-fetching photos
// already fetched by a prior run.
// --file=path.json overrides the default scraped_data.json/vetted_input.json
// lookup — for staging a separate one-off batch (e.g. a fresh Apify run
// saved to its own file) without touching/re-processing the main file.
$onlySku = null;
$limit = null;
$skipImages = false;
$fileOverride = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--sku=')) {
        $onlySku = substr($arg, strlen('--sku='));
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, strlen('--limit='));
    } elseif ($arg === '--skip-images') {
        $skipImages = true;
    } elseif (str_starts_with($arg, '--file=')) {
        $fileOverride = substr($arg, strlen('--file='));
    }
}

if ($fileOverride !== null) {
    $jsonPath = str_starts_with($fileOverride, '/') ? $fileOverride : __DIR__ . '/' . $fileOverride;
} else {
    $jsonPath = __DIR__ . '/scraped_data.json';
    if (!file_exists($jsonPath)) {
        $jsonPath = __DIR__ . '/vetted_input.json';
    }
}
if (!file_exists($jsonPath)) {
    echo "No scraped JSON file found ({$jsonPath}).\n";
    exit(1);
}

$raw = json_decode(file_get_contents($jsonPath), true);
$items = is_array($raw) && isset($raw['items']) ? $raw['items'] : $raw;

if ($onlySku !== null) {
    $items = array_filter($items, function ($item, $index) use ($onlySku) {
        $rawTitle = $item['product']['title'] ?? $item['title'] ?? $item['subject'] ?? 'Commercial Agri Hardware #' . ($index + 1);
        $title = preg_replace('/\s+/', ' ', trim($rawTitle));
        $sku = 'FT-ALI-' . strtoupper(substr(md5($title . $index), 0, 8));
        return $sku === $onlySku;
    }, ARRAY_FILTER_USE_BOTH);
}

if ($limit !== null && $limit > 0) {
    $items = array_slice($items, 0, $limit, true);
}

$storageDir = storage_path('app/public/products');
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$staged = 0;
$rejected = ['price' => 0, 'images' => 0, 'title' => 0, 'logistics' => 0];

foreach ($items as $index => $item) {
    $rawTitle = $item['product']['title'] ?? $item['title'] ?? $item['subject'] ?? 'Commercial Agri Hardware #' . ($index + 1);
    $title = preg_replace('/\s+/', ' ', trim($rawTitle));
    $supplierInfo = ft_extract_supplier($item);
    $supplier = $supplierInfo['name'];

    // Pricing & Weight
    $exchangeRate = ft_extract_exchange_rate($item);
    $usdPrice = ft_extract_usd_price($item);
    $weightKg = ft_extract_weight_kg($item);

    // Real attributes: brand, model, warranty, probe options, battery, etc.
    $specifications = ft_extract_specifications($item);
    $identity = ft_extract_identity($specifications);
    $packageDimensions = ft_extract_package_dimensions($item);
    $sourceUrl = ft_extract_source_url($item);
    $variants = ft_extract_variants($item);
    $reviews = ft_extract_reviews($item);
    $supplierTrust = ft_extract_supplier_trust($item);

    // SKU/slug are derived from the RAW title, unchanged — this is the
    // identity key updateOrCreate matches on, so cleaning it here would
    // create duplicate products instead of updating existing ones.
    //
    // BUT: title+array-index alone isn't a stable identity across two
    // separate scrapes of the exact same real listing — the same product
    // can land at a different array position in a different batch file,
    // producing a different computed SKU and silently creating a
    // duplicate instead of updating the original (confirmed: re-scraping
    // an existing product by its own title, in a 3-item result file,
    // created a second row instead of updating the first). When the
    // scrape carries a source URL, resolve the existing product by its
    // stable Alibaba listing id first — that's the real, rescrape-proof
    // identity — and only fall back to the title+index SKU when no match
    // is found (a genuinely new listing).
    $sku = 'FT-ALI-' . strtoupper(substr(md5($title . $index), 0, 8));
    $alibabaProductId = ft_extract_alibaba_product_id($sourceUrl);

    if ($alibabaProductId !== null) {
        $existingByUrl = \App\Models\Product::where('source_url', 'like', "%_{$alibabaProductId}.html%")->first();

        if ($existingByUrl) {
            $sku = $existingByUrl->sku;
        }
    }

    $slug = Str::slug(mb_substr($title, 0, 60)) . '-' . ($index + 1);

    // Displayed title/copy: cleaned of Alibaba SEO/marketing filler, and
    // grounded in the real scraped brand/model/warranty/specs rather than
    // one identical boilerplate sentence for every product.
    $displayTitle = ft_clean_title($title);
    $carouselImageUrls = $skipImages ? [] : ft_extract_image_urls($item);
    $descriptionImageUrls = $skipImages ? [] : ft_extract_description_images($item);
    // Carousel first (thumbnail-quality, always index 0 when present), then
    // inline description diagrams/photos appended after.
    $imageUrls = array_values(array_unique(array_merge($carouselImageUrls, $descriptionImageUrls)));

    // Landed Cost & Margins — computed before the gatekeeper below since the
    // Logistics & Dimensional Gatekeeper's value-density check needs the
    // real freight/retail figures.
    $costing = ft_compute_costing($usdPrice, $weightKg, $exchangeRate);
    $intlFreightZar = $costing['intlFreightZar'];
    $vatZar = $costing['vatZar'];
    $domesticZar = $costing['domesticZar'];
    $landedCost = $costing['landedCost'];
    $retailPrice = $costing['retailPrice'];

    // Quality gatekeeper — only for items not already in the catalog. An
    // existing product (any status) keeps getting its fields refreshed on
    // rerun regardless of these checks, so a rescrape can never silently
    // unpublish something an admin already approved; only first-time
    // inserts of junk get stopped here.
    $isNew = !\App\Models\Product::where('sku', $sku)->exists();

    if ($isNew) {
        if ($usdPrice < 10.00) {
            echo "Rejected (price \${$usdPrice}, below \$10 minimum): {$title}\n";
            $rejected['price']++;
            continue;
        }

        if (!$skipImages && empty($imageUrls)) {
            echo "Rejected (no usable images): {$title}\n";
            $rejected['images']++;
            continue;
        }

        if (mb_strlen(trim($displayTitle)) < 10) {
            echo "Rejected (unreadable/too-short title): \"{$title}\"\n";
            $rejected['title']++;
            continue;
        }

        // Logistics & Dimensional Gatekeeper — discard items that can't
        // move as a standard air-freight parcel (too heavy, too large,
        // hazardous goods) or whose shipping cost swamps their retail
        // value, BEFORE they're ever inserted (never even land as
        // pending_review clutter).
        $logistics = (new \App\Services\ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => $weightKg,
            'package_dimensions' => $packageDimensions,
            'hazard_text' => $title . ' ' . implode(' ', $specifications),
            'intl_freight_zar' => $intlFreightZar,
            'retail_price_zar' => $retailPrice,
        ]);

        if (!$logistics['passes']) {
            echo "Rejected ({$logistics['detail']}): {$title}\n";
            $rejected['logistics'] = ($rejected['logistics'] ?? 0) + 1;
            continue;
        }
    }

    // Dynamic keyword matching across all new niches
    [$category, $hsCode] = ft_categorize($title);
    $categoryLabel = \App\Enums\ProductCategory::from($category)->label();

    // Brand normalization needs the category label for its house-brand
    // fallback, so this can only run once categoryLabel is known — done
    // before ft_build_fallback_copy() so the fallback description text
    // below also reads the cleaned brand, not a raw junk placeholder.
    $identity['brand_name'] = ft_clean_brand_name($identity['brand_name'], $categoryLabel);

    $copy = ft_build_fallback_copy($categoryLabel, $identity, $specifications);

    // Save Product — status/is_active are only forced to the "just staged"
    // defaults for a brand-new row. A rerun against an already-existing SKU
    // (re-pricing, spec refresh, etc.) must never silently knock an
    // admin-approved product back to pending_review / off the storefront.
    // slug is equally only set on first insert — recomputing it on every
    // rescrape would change a live product's URL out from under anyone who
    // already bookmarked/shared it, since $slug is derived from the raw
    // title + this run's array index, neither of which is stable across
    // separate scrape files of the same real listing.
    $statusDefaults = $isNew ? ['status' => 'pending_review', 'is_active' => false, 'slug' => $slug] : [];

    $product = \App\Models\Product::updateOrCreate(
        ['sku' => $sku],
        array_merge($statusDefaults, [
            'title' => $displayTitle,
            'category' => $category,
            'short_description' => $copy['short_description'],
            'description_html' => $copy['description_html'],
            'original_price_usd' => $usdPrice,
            'supplier_cost_usd' => $usdPrice,
            'exchange_rate' => $exchangeRate,
            'intl_freight_zar' => $intlFreightZar,
            'customs_vat_zar' => $vatZar,
            'domestic_delivery_zar' => $domesticZar,
            'est_weight_kg' => $weightKg,
            'hs_code' => $hsCode,
            'customs_duty_rate' => $costing['dutyRate'],
            'vat_rate' => $costing['vatRate'],
            'landed_cost_zar' => $landedCost,
            'retail_price_zar' => $retailPrice,
            'profit_margin_pct' => $costing['targetMarginPct'],
            'stock_status' => 'in_stock',
            'lead_time_days' => '7-12 business days',
            'supplier_name' => $supplier,
            'supplier_url' => $supplierInfo['url'],
            'source_url' => $sourceUrl,
            'supplier_last_checked_at' => now(),
            'specifications' => $specifications,
            'brand_name' => $identity['brand_name'],
            'model_number' => $identity['model_number'],
            'warranty_period' => $identity['warranty_period'],
            'gross_weight_kg' => $weightKg,
            'package_dimensions' => $packageDimensions,
        ])
    );

    // Save Compliance Audit — is_verified_supplier/has_trade_assurance are
    // unconditionally true here because the sourcing run itself only ever
    // queries Alibaba with verifiedSupplier/tradeAssurance filters on, so
    // every item this script ever sees already satisfies both by
    // construction (not an assumption made here).
    \App\Models\ComplianceAudit::updateOrCreate(
        ['product_id' => $product->id],
        [
            'supplier_name' => $supplier,
            'supplier_years' => $supplierTrust['supplier_years'],
            'is_verified_supplier' => true,
            'has_trade_assurance' => true,
            'audit_verdict' => 'PASS',
            'audit_notes' => 'Imported via staging worker. Ready for admin verification.',
            'suggested_title' => $title,
            'suggested_short_desc' => 'Commercial-grade hardware with verified pricing.',
        ]
    );

    // Sync variants — real per-listing SKU price tiers (see
    // ft_extract_variants()'s docblock), each run through the same
    // LandedCostCalculator every other price in this app goes through.
    // Delete+recreate (same pattern already used for images on a
    // rescrape) since Alibaba's own skuInfoMap ids aren't stable enough
    // to diff against reliably.
    $product->variants()->delete();
    if (!empty($variants)) {
        $calculator = new \App\Services\LandedCostCalculator(
            freightUsdPerKg: (float) \App\Models\Setting::get('air_freight_usd_per_kg', 16),
            domesticDeliveryZar: (float) \App\Models\Setting::get('clearing_agent_fee_zar', 250),
            vatRate: (float) \App\Models\Setting::get('vat_rate', 0.15),
        );
        $targetMarginPct = (float) \App\Models\Setting::get('target_margin_pct', 35);

        foreach ($variants as $variant) {
            $variantCosting = $calculator->calculate(
                $variant['supplier_cost_usd'],
                $weightKg,
                $exchangeRate,
                $costing['dutyRate'],
                $targetMarginPct,
            );

            \App\Models\ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variant['sku'],
                'option_name' => $variant['option_name'],
                'supplier_cost_usd' => $variant['supplier_cost_usd'],
                'landed_cost_zar' => $variantCosting['landed_cost_zar'],
                'retail_price_zar' => $variantCosting['retail_price_zar'],
                'is_default' => $variant['is_default'],
            ]);
        }
    }

    // Sync reviews — real Alibaba buyer feedback, attributed to this
    // listing's verified supplier (Alibaba's own review feed is
    // store-wide, not per-SKU — see ft_extract_reviews()'s docblock in
    // lib_pricing.php). Keyed on source_review_id so a rescrape never
    // duplicates a review already imported. original_review_text always
    // snapshots the latest raw scrape; review_text itself is only set from
    // the raw text on first import (or if never AI-cleaned) — once
    // `products:translate-reviews` has polished a review, a rescrape must
    // never silently clobber that work back to the raw source text.
    foreach ($reviews as $review) {
        $existing = \App\Models\ProductReview::where('product_id', $product->id)
            ->where('source_review_id', $review['source_review_id'])
            ->first();

        if ($existing && $existing->text_cleaned) {
            $existing->update([
                'author_name' => $review['author_name'],
                'rating' => $review['rating'],
                'original_review_text' => $review['review_text'],
                'review_date' => $review['review_date'],
            ]);
            continue;
        }

        \App\Models\ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'source_review_id' => $review['source_review_id']],
            [
                'author_name' => $review['author_name'],
                'rating' => $review['rating'],
                'review_text' => $review['review_text'],
                'original_review_text' => $review['review_text'],
                'verified_purchase' => true,
                'review_date' => $review['review_date'],
                'source' => 'alibaba',
            ]
        );
    }

    // Download & Link Photos Locally — every scraped photo, not capped at 5.
    // $imageUrls was already computed above for the gatekeeper check (empty
    // under --skip-images, a text-only re-run with no network round-trips).
    if (!empty($imageUrls)) {
        $product->images()->delete();

        // Clear any stale local files from a previous run — the filename is
        // just a positional index (p_{id}_{n}.jpg), so if the source URL set
        // changed (e.g. switching from thumbnail to full-res URLs) a file
        // already sitting at that path would otherwise be treated as
        // "already downloaded" and never get refreshed.
        foreach (glob(storage_path('app/public/products/p_' . $product->id . '_*.jpg')) as $staleFile) {
            unlink($staleFile);
        }

        foreach ($imageUrls as $imgIdx => $sourceUrl) {
            $sourceUrl = ft_strip_thumbnail_suffix($sourceUrl);
            $filename = 'products/p_' . $product->id . '_' . ($imgIdx + 1) . '.jpg';
            $fullPath = storage_path('app/public/' . $filename);

            if (!file_exists($fullPath) || filesize($fullPath) < 500) {
                try {
                    $res = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Referer' => 'https://www.alibaba.com/'
                    ])->timeout(8)->get($sourceUrl);

                    if ($res->successful() && strlen($res->body()) > 500) {
                        file_put_contents($fullPath, $res->body());
                    }
                } catch (\Exception $e) {}
            }

            \App\Models\ProductImage::create([
                'product_id' => $product->id,
                'original_url' => $sourceUrl,
                'local_path' => $filename,
                'is_thumbnail' => ($imgIdx === 0),
                'sort_order' => $imgIdx,
            ]);
        }
    }

    echo "Staged: [{$category}] {$product->title}\n";
    $staged++;
}

$totalRejected = array_sum($rejected);
echo "\nDone staging {$staged} products! Rejected {$totalRejected} "
    . "(price: {$rejected['price']}, images: {$rejected['images']}, title: {$rejected['title']}, logistics: {$rejected['logistics']}).\n";
