<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

require __DIR__ . '/lib_pricing.php';

$jsonPath = __DIR__ . '/scraped_data.json';
if (!file_exists($jsonPath)) {
    $jsonPath = __DIR__ . '/vetted_input.json';
}
if (!file_exists($jsonPath)) {
    echo "No scraped JSON file found in worker directory.\n";
    exit(1);
}

$raw = json_decode(file_get_contents($jsonPath), true);
$items = is_array($raw) && isset($raw['items']) ? $raw['items'] : $raw;

// Optional CLI filters for targeted test runs: --sku=FT-ALI-XXXXXXXX or --limit=N
// (matched against the SKU this script itself would compute for each item, so
// callers can verify a single known listing without re-ingesting everything).
// --skip-images updates every DB field except photos, without the network
// round-trips — for re-applying a text-only change (title cleanup, pricing
// fix, etc.) across the whole catalog fast, without re-fetching photos
// already fetched by a prior run.
$onlySku = null;
$limit = null;
$skipImages = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--sku=')) {
        $onlySku = substr($arg, strlen('--sku='));
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, strlen('--limit='));
    } elseif ($arg === '--skip-images') {
        $skipImages = true;
    }
}

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
$rejected = ['price' => 0, 'images' => 0, 'title' => 0];

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

    // SKU/slug are derived from the RAW title, unchanged — this is the
    // identity key updateOrCreate matches on, so cleaning it here would
    // create duplicate products instead of updating existing ones.
    $sku = 'FT-ALI-' . strtoupper(substr(md5($title . $index), 0, 8));
    $slug = Str::slug(mb_substr($title, 0, 60)) . '-' . ($index + 1);

    // Displayed title/copy: cleaned of Alibaba SEO/marketing filler, and
    // grounded in the real scraped brand/model/warranty/specs rather than
    // one identical boilerplate sentence for every product.
    $displayTitle = ft_clean_title($title);
    $imageUrls = $skipImages ? [] : ft_extract_image_urls($item);

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
    }

    // Landed Cost & Margins
    $costing = ft_compute_costing($usdPrice, $weightKg, $exchangeRate);
    $intlFreightZar = $costing['intlFreightZar'];
    $vatZar = $costing['vatZar'];
    $domesticZar = $costing['domesticZar'];
    $landedCost = $costing['landedCost'];
    $retailPrice = $costing['retailPrice'];

    // Dynamic keyword matching across all new niches
    [$category, $hsCode] = ft_categorize($title);
    $categoryLabel = \App\Enums\ProductCategory::from($category)->label();
    $copy = ft_build_fallback_copy($categoryLabel, $identity, $specifications);

    // Save Product — status/is_active are only forced to the "just staged"
    // defaults for a brand-new row. A rerun against an already-existing SKU
    // (re-pricing, spec refresh, etc.) must never silently knock an
    // admin-approved product back to pending_review / off the storefront.
    $statusDefaults = $isNew ? ['status' => 'pending_review', 'is_active' => false] : [];

    $product = \App\Models\Product::updateOrCreate(
        ['sku' => $sku],
        array_merge($statusDefaults, [
            'title' => $displayTitle,
            'slug' => $slug,
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

    // Save Compliance Audit
    \App\Models\ComplianceAudit::updateOrCreate(
        ['product_id' => $product->id],
        [
            'supplier_name' => $supplier,
            'audit_verdict' => 'PASS',
            'audit_notes' => 'Imported via staging worker. Ready for admin verification.',
            'suggested_title' => $title,
            'suggested_short_desc' => 'Commercial-grade hardware with verified pricing.',
        ]
    );

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
            // Alibaba CDN thumbnail URLs carry a "_{width}x{height}" suffix
            // (e.g. "_50x50.jpg", "_220x220.jpg") right before the
            // extension — stripping it resolves to the original full-size
            // source image, whatever size was embedded in the listing.
            $sourceUrl = preg_replace('/_\d{2,4}x\d{2,4}(\.(?:jpg|jpeg|png|webp))$/i', '$1', $sourceUrl);
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
    . "(price: {$rejected['price']}, images: {$rejected['images']}, title: {$rejected['title']}).\n";
