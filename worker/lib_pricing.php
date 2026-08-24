<?php

/**
 * Shared price/weight/supplier extraction for Alibaba scrape payloads.
 *
 * product.price is a pre-formatted ZAR range string (e.g. "R 14 551,34-15 717,42"),
 * not a USD number — casting it directly to float always yields 0. The real USD
 * range lives at detail.price.productRangePrices.dollarPriceRangeLow, but that key
 * is missing on a large share of scraped items, so we fall back to parsing the low
 * end of the ZAR range text and converting it with the item's own currency rate.
 */

function ft_extract_exchange_rate(array $item): float
{
    $rate = $item['detail']['price']['currencyRule']['rate'] ?? null;

    return ($rate && $rate > 0) ? (float) $rate : 18.50;
}

function ft_extract_usd_price(array $item): float
{
    $range = $item['detail']['price']['productRangePrices'] ?? [];
    if (!empty($range['dollarPriceRangeLow']) && $range['dollarPriceRangeLow'] > 0) {
        return round((float) $range['dollarPriceRangeLow'], 2);
    }

    $priceText = $item['product']['price'] ?? '';
    if ($priceText !== '') {
        $clean = preg_replace('/[^\d,.\-]/u', '', $priceText);
        $low = explode('-', (string) $clean)[0] ?? '';
        $zarLow = (float) str_replace(',', '.', $low);
        $rate = ft_extract_exchange_rate($item);

        if ($zarLow > 0 && $rate > 0) {
            return round($zarLow / $rate, 2);
        }
    }

    return 0.0;
}

function ft_extract_weight_kg(array $item): float
{
    $weight = (float) ($item['detail']['trade']['logisticInfo']['unitWeight'] ?? 0);

    return $weight > 0 ? $weight : 0.5;
}

function ft_extract_supplier(array $item): array
{
    return [
        'name' => $item['supplier']['name'] ?? $item['companyName'] ?? 'Alibaba Verified Supplier',
        'url' => $item['supplier']['homeUrl'] ?? $item['supplier']['profileUrl'] ?? null,
    ];
}

/**
 * Delegates to the canonical costing formula (App\Services\LandedCostCalculator,
 * mirrored by worker/src/lib/landedCost.js — see that file's docblock for why
 * "customs_vat_zar" is a single incremental duty+VAT figure, not two stacked
 * ones) instead of duplicating it with hardcoded constants ($250/kg freight,
 * flat 10% duty, 38% margin) that ignore the admin-configurable Settings the
 * rest of the app uses.
 */
function ft_compute_costing(float $usdPrice, float $weightKg, float $forex): array
{
    $calculator = new \App\Services\LandedCostCalculator(
        freightUsdPerKg: (float) \App\Models\Setting::get('air_freight_usd_per_kg', 16),
        domesticDeliveryZar: (float) \App\Models\Setting::get('clearing_agent_fee_zar', 250),
        vatRate: (float) \App\Models\Setting::get('vat_rate', 0.15),
    );

    // No per-listing duty-rate signal exists in the scrape data yet — same
    // fixed 10% every ingestion path in this app currently uses.
    $dutyRate = 0.10;
    $targetMarginPct = (float) \App\Models\Setting::get('target_margin_pct', 35);

    $result = $calculator->calculate($usdPrice, $weightKg, $forex, $dutyRate, $targetMarginPct);

    return [
        'intlFreightZar' => $result['intl_freight_zar'],
        'vatZar' => $result['customs_vat_zar'],
        'domesticZar' => $result['domestic_delivery_zar'],
        'landedCost' => $result['landed_cost_zar'],
        'retailPrice' => $result['retail_price_zar'],
        'dutyRate' => $dutyRate,
        'vatRate' => (float) \App\Models\Setting::get('vat_rate', 0.15),
        'targetMarginPct' => $targetMarginPct,
    ];
}

/**
 * Deduped key→value map from the Alibaba "Key attributes" table
 * (detail.specs). Every scraped item carries each attrName duplicated in
 * that array — last-wins here; blank name/value rows are dropped.
 */
function ft_extract_specifications(array $item): array
{
    $map = [];

    foreach ($item['detail']['specs'] ?? [] as $row) {
        $name = trim((string) ($row['attrName'] ?? ''));
        $value = trim((string) ($row['attrValue'] ?? ''));
        if ($name !== '' && $value !== '') {
            $map[$name] = $value;
        }
    }

    return $map;
}

/**
 * Case-insensitive lookup against a lower-cased index of the specifications
 * map, trying each candidate key in order and returning the first match.
 * Supplier attribute-table key casing/wording is inconsistent across
 * listings (e.g. "Brand Name" vs "brand name" vs "Brand" — confirmed by
 * survey of the real scrape data: 224x "Brand Name", 217x "brand name",
 * 203x "Model Number", 166x "model number", 154x "Warranty", 189x
 * "warranty", plus stray variants like "Model No"/"Standard Warranty").
 */
function ft_find_spec(array $lowerIndex, array $candidateKeys): ?string
{
    foreach ($candidateKeys as $candidate) {
        if (isset($lowerIndex[strtolower($candidate)])) {
            return $lowerIndex[strtolower($candidate)];
        }
    }

    return null;
}

/** Brand/model/warranty are direct 1:1 fields inside specifications — no AI needed. */
function ft_extract_identity(array $specifications): array
{
    $lowerIndex = [];
    foreach ($specifications as $key => $value) {
        $lowerIndex[strtolower($key)] = $value;
    }

    return [
        'brand_name' => ft_find_spec($lowerIndex, ['Brand Name', 'Brand name', 'Brand']),
        'model_number' => ft_find_spec($lowerIndex, ['Model Number', 'Model No', 'Model name', 'Model']),
        'warranty_period' => ft_find_spec($lowerIndex, [
            'Warranty', 'Standard Warranty', 'Warranty of core components', 'Warranty(year)',
        ]),
    ];
}

/**
 * All full-resolution photo URLs from detail.media (type === 'image', so any
 * video entry in the same array is skipped). Falls back to the lower-
 * fidelity product.images URL list only when detail.media is absent. No
 * count cap — every URL returned here gets a ProductImage row.
 */
function ft_extract_image_urls(array $item): array
{
    $urls = [];

    foreach ($item['detail']['media'] ?? [] as $entry) {
        if (($entry['type'] ?? null) === 'image' && !empty($entry['imageUrl']['big'])) {
            $urls[] = $entry['imageUrl']['big'];
        }
    }

    if (!empty($urls)) {
        return array_values(array_unique($urls));
    }

    $fallback = $item['product']['images'] ?? $item['images'] ?? [];
    $plain = [];
    foreach ($fallback as $img) {
        if (is_string($img)) $plain[] = $img;
        elseif (is_array($img) && isset($img['url'])) $plain[] = $img['url'];
    }

    return array_values(array_unique($plain));
}

/**
 * Keyword-based category + HS code guess from a listing title. Shared by
 * every ingestion path (the raw Alibaba scrape and the pre-vetted
 * vetted_input.json batch) so a category fix only has to be made once.
 */
function ft_categorize(string $title): array
{
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
    } elseif (str_contains($lower, 'hilux') || str_contains($lower, 'bakkie') || str_contains($lower, '4x4') || str_contains($lower, 'headlight') || str_contains($lower, 'towbar') || str_contains($lower, 'canopy') || str_contains($lower, 'battery isolator') || str_contains($lower, ' vsr ')) {
        $category = 'vehicle_accessories';
        $hsCode = '8708.29';
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

    return [$category, $hsCode];
}

/** The real Alibaba listing URL (distinct from supplier_url, the supplier's company profile page). */
function ft_extract_source_url(array $item): ?string
{
    $url = $item['product']['url'] ?? $item['url'] ?? null;

    return (is_string($url) && $url !== '') ? $url : null;
}

/** Raw "LxWxH" package dimension string in cm, stored as-is — format varies by supplier. */
function ft_extract_package_dimensions(array $item): ?string
{
    $size = $item['detail']['trade']['logisticInfo']['unitSize'] ?? null;
    $size = is_string($size) ? trim($size) : null;

    return $size !== '' ? $size : null;
}

/**
 * Alibaba listing titles are SEO-keyword-stuffed for marketplace search, not
 * written for a retail customer: repeated category words, marketing filler
 * ("Good Price", "Hot Selling", "OEM/ODM", "2025 New Design"), and stray
 * non-English boilerplate ("Indicador De Peso" — Spanish for "Weight
 * Indicator", already redundant with the English wording elsewhere in the
 * same title). This strips only that filler — never touches real technical
 * tokens (model numbers, capacities, standards) — and de-duplicates
 * immediately-repeated words. Deliberately conservative: no re-casing (would
 * risk mangling real acronyms like XK3190, RS232, LCD, IP65), no rewriting.
 * This is a mechanical cleanup pass; PolishProductCopy's AI rewrite (when
 * quota allows) produces genuinely better copy from the same source data.
 */
function ft_clean_title(string $title): string
{
    static $fillerPhrases = [
        'good price', 'best price', 'low price', 'factory price', 'dealer price',
        'wholesale price', 'special price', 'on sale', 'hot sale', 'hot selling',
        'best selling', 'top selling', 'new arrival', 'new design', 'high quality',
        'top quality', 'premium quality', 'oem/odm', 'oem odm', 'oem & odm', ' oem ',
        ' odm ', 'customizable', 'customized', 'wholesale', 'factory supply',
        'factory direct', 'factory outlet', 'source factory', 'in stock',
        'free shipping', 'fast delivery', 'indicador de peso', 'made in china',
    ];

    // Scraped titles sometimes carry literal HTML entities ("&amp;") and a
    // stray "|" used as an ad-hoc separator — normalize both before cleaning.
    $normalized = html_entity_decode($title, ENT_QUOTES | ENT_HTML5);
    $normalized = str_replace('|', ' ', $normalized);

    $clean = ' ' . $normalized . ' ';
    foreach ($fillerPhrases as $phrase) {
        $clean = preg_replace('/(?<![a-z0-9])' . preg_quote($phrase, '/') . '(?![a-z0-9])/i', ' ', $clean);
    }

    // Leading trend-chasing year tag ("2025 Hot Selling ...") and a bare
    // leading "China " marketing tag — only at the very start, never mid-title
    // where a year could be a real spec (e.g. a certification year).
    $clean = preg_replace('/^\s*(20\d{2}\s+|china\s+)/i', '', $clean);

    // Collapse whitespace, then drop an immediately-repeated word (case-insensitive).
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    $words = explode(' ', $clean);
    $deduped = [];
    foreach ($words as $word) {
        if (empty($deduped) || strcasecmp(end($deduped), $word) !== 0) {
            $deduped[] = $word;
        }
    }
    $clean = trim(implode(' ', $deduped), " \t\n\r\0\x0B-,");

    if ($clean === '') {
        return trim($title);
    }

    // Cut at a word boundary rather than mid-word if still long, then strip
    // any trailing connector word(s) a hard cut can leave dangling (e.g.
    // "...Cattle Sheep and", "...Rectal Probe for").
    if (mb_strlen($clean) > 90) {
        $clean = mb_substr($clean, 0, 90);
        $clean = preg_replace('/\s+\S*$/', '', $clean);

        $trailingStopwords = '/\s+(for|and|or|with|the|a|an|of|to|in|on|&)$/i';
        while (preg_match($trailingStopwords, $clean)) {
            $clean = preg_replace($trailingStopwords, '', $clean);
        }
    }

    return $clean;
}

/**
 * Real per-product short_description + description_html grounded in the
 * actual scraped brand/model/warranty/specifications — replaces the single
 * identical boilerplate sentence every staged product previously shared
 * (which told a buyer nothing product-specific). Deterministic, not AI —
 * PolishProductCopy overwrites this with genuinely better AI copy when
 * Gemini quota is available; this is what every product has in the meantime.
 */
function ft_build_fallback_copy(string $categoryLabel, array $identity, array $specifications): array
{
    $bits = [];
    if ($identity['brand_name']) {
        $bits[] = $identity['model_number']
            ? "{$identity['brand_name']} {$identity['model_number']}"
            : $identity['brand_name'];
    } elseif ($identity['model_number']) {
        $bits[] = "model {$identity['model_number']}";
    }

    $intro = $bits
        ? 'Genuine ' . implode(' ', $bits) . " {$categoryLabel}, imported and landed in South Africa with duty, VAT, and delivery already included."
        : "Commercial-grade {$categoryLabel}, imported and landed in South Africa with duty, VAT, and delivery already included.";

    $shortDescription = $intro;
    if ($identity['warranty_period']) {
        $shortDescription .= " Backed by a {$identity['warranty_period']} supplier warranty.";
    }

    $paragraphs = ['<p>' . htmlspecialchars($intro) . '</p>'];

    // A handful of real, farmer-relevant spec lines beat one vague sentence —
    // pull from whatever the listing actually stated, skip identity fields
    // already surfaced elsewhere on the page.
    $skipKeys = ['Brand Name', 'Model Number', 'Place of Origin'];
    $highlightSpecs = array_filter($specifications, fn ($k) => !in_array($k, $skipKeys, true), ARRAY_FILTER_USE_KEY);
    $highlightSpecs = array_slice($highlightSpecs, 0, 6, true);

    if (!empty($highlightSpecs)) {
        $items = [];
        foreach ($highlightSpecs as $key => $value) {
            $items[] = '<li>' . htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '</li>';
        }
        $paragraphs[] = '<ul>' . implode('', $items) . '</ul>';
    }

    $paragraphs[] = '<p>All-in landed pricing — customs clearance and door-to-door delivery are handled for you, with nothing extra to pay on arrival.</p>';

    return [
        'short_description' => $shortDescription,
        'description_html' => implode('', $paragraphs),
    ];
}
