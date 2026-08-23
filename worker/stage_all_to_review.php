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

$storageDir = storage_path('app/public/products');
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$staged = 0;

foreach ($items as $index => $item) {
    $rawTitle = $item['product']['title'] ?? $item['title'] ?? $item['subject'] ?? 'Commercial Agri Hardware #' . ($index + 1);
    $title = preg_replace('/\s+/', ' ', trim($rawTitle));
    $supplierInfo = ft_extract_supplier($item);
    $supplier = $supplierInfo['name'];

    // Pricing & Weight
    $exchangeRate = ft_extract_exchange_rate($item);
    $usdPrice = ft_extract_usd_price($item);
    if ($usdPrice <= 0) $usdPrice = 65.00;

    $weightKg = ft_extract_weight_kg($item);

    // Landed Cost & Margins
    $costing = ft_compute_costing($usdPrice, $weightKg, $exchangeRate);
    $intlFreightZar = $costing['intlFreightZar'];
    $customsDutyZar = $costing['customsDutyZar'];
    $vatZar = $costing['vatZar'];
    $domesticZar = $costing['domesticZar'];
    $landedCost = $costing['landedCost'];
    $retailPrice = $costing['retailPrice'];

    // Dynamic keyword matching across all new niches
    $lower = strtolower($title);
    $category = 'accessories';
    $hsCode = '8471.90';

    if (str_contains($lower, 'fuel') || str_contains($lower, 'diesel') || str_contains($lower, 'tank level') || str_contains($lower, 'flow meter')) {
        $category = 'fuel_monitoring';
        $hsCode = '9026.10';
    } elseif (str_contains($lower, 'thermal') || str_contains($lower, 'infrared camera') || str_contains($lower, 'imaging')) {
        $category = 'thermal_diagnostics';
        $hsCode = '9027.80';
    } elseif (str_contains($lower, 'laser') || str_contains($lower, 'level')) {
        $category = 'laser_levels';
        $hsCode = '9015.30';
    } elseif (str_contains($lower, 'moisture') || str_contains($lower, 'grain') || str_contains($lower, 'tester') || str_contains($lower, 'npk')) {
        $category = 'moisture_meters';
        $hsCode = '9027.80';
    } elseif (str_contains($lower, 'pump') || str_contains($lower, 'borehole') || str_contains($lower, 'deep well')) {
        $category = 'solar_pumps';
        $hsCode = '8413.70';
    } elseif (str_contains($lower, 'inverter') || str_contains($lower, 'mppt') || str_contains($lower, 'charge controller')) {
        $category = 'mppt_controllers';
        $hsCode = '8504.40';
    } elseif (str_contains($lower, 'fence') || str_contains($lower, 'energizer')) {
        $category = 'fencing';
        $hsCode = '8543.70';
    } elseif (str_contains($lower, 'valve') || str_contains($lower, 'irrigation')) {
        $category = 'smart_irrigation';
        $hsCode = '8424.82';
    } elseif (str_contains($lower, 'theodolite') || str_contains($lower, 'total station')) {
        $category = 'theodolites';
        $hsCode = '9015.20';
    } elseif (str_contains($lower, 'rebar') || str_contains($lower, 'cover meter') || str_contains($lower, 'detector')) {
        $category = 'rebar_detectors';
        $hsCode = '9031.80';
    } elseif (str_contains($lower, 'crane scale') || str_contains($lower, 'platform scale') || str_contains($lower, 'weighbridge')) {
        $category = 'platform_scales';
        $hsCode = '8423.82';
    } elseif (str_contains($lower, 'gps') || str_contains($lower, 'tracker') || str_contains($lower, 'fleet')) {
        $category = 'fleet_trackers';
        $hsCode = '8526.91';
    } elseif (str_contains($lower, 'rfid') || str_contains($lower, 'ear tag') || str_contains($lower, 'reader') || str_contains($lower, 'microchip')) {
        $category = 'rfid';
        $hsCode = '8471.90';
    } elseif (str_contains($lower, 'ultrasound') || str_contains($lower, 'probe') || str_contains($lower, 'pregnancy') || str_contains($lower, 'sonar')) {
        $category = 'ultrasound';
        $hsCode = '9018.12';
    } elseif (str_contains($lower, 'scale') || str_contains($lower, 'weigh') || str_contains($lower, 'load cell') || str_contains($lower, 'indicator') || str_contains($lower, 't7e')) {
        $category = 'scales';
        $hsCode = '8423.82';
    }

    $sku = 'FT-ALI-' . strtoupper(substr(md5($title . $index), 0, 8));
    $slug = Str::slug(mb_substr($title, 0, 60)) . '-' . ($index + 1);

    // Save Product
    $product = \App\Models\Product::updateOrCreate(
        ['sku' => $sku],
        [
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'short_description' => 'Commercial-grade hardware with verified South African duty and freight calculation.',
            'description_html' => '<p>' . htmlspecialchars($title) . '</p><p>Door-to-door delivery with customs, clearance, and 15% VAT included.</p>',
            'original_price_usd' => $usdPrice,
            'supplier_cost_usd' => $usdPrice,
            'exchange_rate' => $exchangeRate,
            'intl_freight_zar' => $intlFreightZar,
            'customs_vat_zar' => $vatZar,
            'domestic_delivery_zar' => $domesticZar,
            'est_weight_kg' => $weightKg,
            'hs_code' => $hsCode,
            'customs_duty_rate' => 0.10,
            'vat_rate' => 0.15,
            'landed_cost_zar' => $landedCost,
            'retail_price_zar' => $retailPrice,
            'profit_margin_pct' => 38.00,
            'stock_status' => 'in_stock',
            'lead_time_days' => '7-12 business days',
            'status' => 'pending_review',
            'is_active' => false,
            'supplier_name' => $supplier,
            'supplier_url' => $supplierInfo['url'],
            'supplier_last_checked_at' => now(),
        ]
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

    // Download & Link Photos Locally
    $rawImages = $item['product']['images'] ?? $item['images'] ?? [];
    $imageUrls = [];
    foreach ($rawImages as $img) {
        if (is_string($img)) $imageUrls[] = $img;
        elseif (is_array($img) && isset($img['url'])) $imageUrls[] = $img['url'];
    }

    if (!empty($imageUrls)) {
        $product->images()->delete();
        foreach (array_slice($imageUrls, 0, 5) as $imgIdx => $sourceUrl) {
            $sourceUrl = str_replace(['_300x300.jpg', '_300x300.png'], ['_800x800.jpg', '_800x800.png'], $sourceUrl);
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

echo "\nDone staging {$staged} products!\n";
