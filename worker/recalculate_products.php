<?php

/**
 * One-off maintenance pass: re-derives pricing and supplier fields for every
 * existing product that was staged from scraped_data.json, using the fixed
 * extraction logic in lib_pricing.php. Corrects the fallback values every
 * mis-parsed row previously shared (original_price_usd=$65.00,
 * retail_price_zar=R2,759.66, supplier_name="Alibaba Supplier (Vetting
 * Pending)"). Does not touch images, title, category, slug, or status.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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

$updated = 0;
$notFound = 0;

foreach ($items as $index => $item) {
    $rawTitle = $item['product']['title'] ?? $item['title'] ?? $item['subject'] ?? 'Commercial Agri Hardware #' . ($index + 1);
    $title = preg_replace('/\s+/', ' ', trim($rawTitle));
    $sku = 'FT-ALI-' . strtoupper(substr(md5($title . $index), 0, 8));

    $product = \App\Models\Product::where('sku', $sku)->first();
    if (!$product) {
        $notFound++;
        continue;
    }

    $supplierInfo = ft_extract_supplier($item);
    $exchangeRate = ft_extract_exchange_rate($item);
    $usdPrice = ft_extract_usd_price($item);
    if ($usdPrice <= 0) $usdPrice = 65.00;

    $weightKg = ft_extract_weight_kg($item);
    $costing = ft_compute_costing($usdPrice, $weightKg, $exchangeRate);

    $product->update([
        'original_price_usd' => $usdPrice,
        'supplier_cost_usd' => $usdPrice,
        'exchange_rate' => $exchangeRate,
        'est_weight_kg' => $weightKg,
        'intl_freight_zar' => $costing['intlFreightZar'],
        'customs_vat_zar' => $costing['vatZar'],
        'domestic_delivery_zar' => $costing['domesticZar'],
        'landed_cost_zar' => $costing['landedCost'],
        'retail_price_zar' => $costing['retailPrice'],
        'supplier_name' => $supplierInfo['name'],
        'supplier_url' => $supplierInfo['url'],
        'supplier_last_checked_at' => now(),
    ]);

    $updated++;
}

echo "Recalculated {$updated} products. {$notFound} scraped items had no matching product (skipped).\n";
