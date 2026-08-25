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
 * Real per-listing price tiers from Alibaba's own SKU table — confirmed
 * against a live re-scrape of a real listing (a "Radar Water Level
 * Sensor" priced independently per range: 7m/15m/40m/80m, plus a
 * "Wireless module + server + software" package option). Shape:
 *
 *   detail.skus.skuAttrs  = [{ id, name, values: [{ id, name, selected }] }, ...]
 *   detail.skus.skuInfoMap = { "attrId:valueId;attrId2:valueId2;": { dollarPrice, id, ... }, ... }
 *
 * Returns rows shaped for ProductVariant::create() — {sku, option_name,
 * supplier_cost_usd, is_default} — NOT run through LandedCostCalculator
 * here (that needs Setting::get()/the app's Eloquent models, which this
 * plain-PHP-include file has no business depending on); the caller in
 * stage_all_to_review.php does that per row.
 */
function ft_extract_variants(array $item): array
{
    $skus = $item['detail']['skus'] ?? [];
    $skuAttrs = $skus['skuAttrs'] ?? [];
    $skuInfoMap = $skus['skuInfoMap'] ?? [];

    if (!is_array($skuAttrs) || !is_array($skuInfoMap) || empty($skuInfoMap)) {
        return [];
    }

    // Only attribute groups with 2+ real values represent an actual
    // customer choice — a single-value group (seen in real data: "Mfg.
    // Date Code" with only a "-" placeholder value) isn't one, even
    // though it still appears in every skuInfoMap key.
    $meaningfulGroups = [];
    foreach ($skuAttrs as $attr) {
        $attrId = $attr['id'] ?? null;
        $values = $attr['values'] ?? [];
        if ($attrId === null || !is_array($values) || count($values) < 2) {
            continue;
        }

        $valueNames = [];
        $selectedValueId = null;
        foreach ($values as $value) {
            $valueId = $value['id'] ?? null;
            $name = trim((string) ($value['name'] ?? ''));
            if ($valueId === null || $name === '') {
                continue;
            }
            $valueNames[$valueId] = $name;
            if (!empty($value['selected'])) {
                $selectedValueId = $valueId;
            }
        }

        if (!empty($valueNames)) {
            $meaningfulGroups[$attrId] = ['values' => $valueNames, 'selected' => $selectedValueId];
        }
    }

    if (empty($meaningfulGroups)) {
        return [];
    }

    $variants = [];

    foreach ($skuInfoMap as $compositeKey => $info) {
        $usdPrice = (float) ($info['dollarPrice'] ?? 0);
        if ($usdPrice <= 0) {
            continue;
        }

        // Composite key format: "attrId:valueId;attrId2:valueId2;" —
        // parse every pair, but only keep the ones belonging to a
        // meaningful (2+ value) group.
        $optionParts = [];
        $isDefault = true;
        foreach (explode(';', trim((string) $compositeKey, ';')) as $pair) {
            if ($pair === '' || !str_contains($pair, ':')) {
                continue;
            }
            [$attrId, $valueId] = array_map('trim', explode(':', $pair, 2));
            $attrId = is_numeric($attrId) ? (int) $attrId : $attrId;
            $valueId = is_numeric($valueId) ? (int) $valueId : $valueId;

            if (!isset($meaningfulGroups[$attrId])) {
                continue;
            }

            $optionParts[] = $meaningfulGroups[$attrId]['values'][$valueId] ?? null;

            if ($meaningfulGroups[$attrId]['selected'] !== $valueId) {
                $isDefault = false;
            }
        }

        $optionParts = array_filter($optionParts);
        if (empty($optionParts)) {
            continue;
        }

        $variants[] = [
            'sku' => (string) ($info['id'] ?? ''),
            'option_name' => implode(' / ', $optionParts),
            'supplier_cost_usd' => round($usdPrice, 2),
            'is_default' => $isDefault,
        ];
    }

    return $variants;
}

/**
 * Real per-product buyer reviews from the scrape's top-level `reviews[]`
 * array (only present when the actor run had includeReviews:true — billed
 * per review, a separate cost from a normal search) — genuine masked
 * buyer names ("V************o", Alibaba's own display convention, not
 * something to unmask or invent), real 1-5 quality scores, real dates,
 * real free text.
 */
function ft_extract_reviews(array $item): array
{
    $reviews = $item['reviews'] ?? [];
    if (!is_array($reviews)) {
        return [];
    }

    $out = [];

    foreach ($reviews as $review) {
        $buyerName = trim((string) ($review['buyer']['anonymousName'] ?? ''));
        $productReviews = $review['productReview'] ?? [];

        foreach ($productReviews as $pr) {
            $text = trim((string) ($pr['reviewContent'] ?? ''));
            $reviewId = $pr['reviewId'] ?? null;
            $score = $pr['latitudeScore']['score'] ?? null;

            if ($buyerName === '' || $reviewId === null || $score === null) {
                continue;
            }

            $reviewDate = null;
            if (!empty($review['reviewTime'])) {
                try {
                    $reviewDate = (new DateTime($review['reviewTime']))->format('Y-m-d');
                } catch (Exception) {
                    $reviewDate = null;
                }
            }

            $out[] = [
                'source_review_id' => (string) $reviewId,
                'author_name' => $buyerName,
                'rating' => max(1, min(5, (int) round((float) $score))),
                'review_text' => $text !== '' ? $text : null,
                'review_date' => $reviewDate,
            ];
        }
    }

    return $out;
}

/** Real supplier trust signals ("7 yrs" Gold Supplier, store service score) — currently never populated by this ingestion path, only by the Node AI pipeline's own extraction. */
function ft_extract_supplier_trust(array $item): array
{
    $yearsRaw = (string) ($item['supplier']['goldSupplierYears'] ?? '');
    preg_match('/(\d+)/', $yearsRaw, $matches);

    return [
        'supplier_years' => isset($matches[1]) ? (int) $matches[1] : null,
        'service_score' => isset($item['supplier']['serviceScore']) ? (float) $item['supplier']['serviceScore'] : null,
    ];
}

/**
 * Real Alibaba CDN thumbnail URLs append a full second
 * "_{width}x{height}.{ext}" suffix AFTER the image's own real extension
 * (confirmed against real data: "...50R.jpg_300x300.jpg", not
 * "...50R_300x300.jpg" — every one of 7,422 sampled thumbnail URLs across
 * the current scrape corpus follows this double-extension form). Naively
 * replacing only the "_WxH" part while keeping one trailing extension
 * leaves a dangling double extension ("...jpg.jpg") that 404s — verified
 * live against Alibaba's CDN. This strips the whole "_WxH" + duplicate
 * extension tail back to the single real extension, resolving to the
 * genuine full-resolution original (verified: 4x the byte size of the
 * thumbnail on the same real URL). The leading extension-before-"_WxH" is
 * optional so a hypothetical single-extension form ("foo_220x220.jpg",
 * not currently seen in this corpus but named as an example case) still
 * resolves correctly. Also handles a bare format-conversion suffix with no
 * dimensions ("foo.jpg_.webp") the same way. Shared by the carousel-image
 * and description-image extractors so there's exactly one place this
 * pattern lives.
 */
function ft_strip_thumbnail_suffix(string $url): string
{
    $url = preg_replace(
        '/(?:\.(?:jpg|jpeg|png|webp))?_\d{2,4}x\d{2,4}(\.(?:jpg|jpeg|png|webp))$/i',
        '$1',
        $url
    );

    return preg_replace(
        '/(\.(?:jpg|jpeg|png|webp))_\.(?:jpg|jpeg|png|webp)$/i',
        '$1',
        $url
    );
}

/**
 * Inline diagrams/photos embedded in the rich Alibaba description HTML
 * (detail.descriptionHtml.html) — real DOM parsing (not regex) since it's
 * real, messy third-party HTML. The raw HTML itself is never rendered
 * directly on our own pages (it carries its own <STYLE> blocks scoped to
 * supplier-chosen ids and JSON data-attributes — real style-bleed/bloat
 * risk); this only lifts the image URLs out of it for the gallery.
 */
function ft_extract_description_images(array $item): array
{
    $html = $item['detail']['descriptionHtml']['html'] ?? '';
    if (!is_string($html) || trim($html) === '') {
        return [];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $urls = [];
    foreach ($dom->getElementsByTagName('img') as $img) {
        // Lazy-loaded images carry the real URL in data-src and a generic
        // "img-placeholder.png" in src itself — data-src wins whenever
        // it's present, not just when src is empty.
        $src = trim($img->getAttribute('data-src') ?: $img->getAttribute('src'));
        if ($src === '' || str_contains($src, 'img-placeholder')) {
            continue;
        }
        if (str_starts_with($src, '//')) {
            $src = 'https:' . $src;
        }
        $urls[] = ft_strip_thumbnail_suffix($src);
    }

    return array_values(array_unique($urls));
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

/**
 * Real, deterministic brand cleanup — a mechanical pass (mirrors
 * ft_clean_title()'s relationship to PolishProductCopy's AI rewrite), not
 * an AI call, so it runs on every staged item at zero extra cost.
 *
 * Survey of the current live catalog's 307 populated Brand Name values
 * found the raw "Brand Name" spec field itself is already mostly clean
 * (e.g. "GREAT FARM", "HONDETEC", "Vetfine") since it's a distinct field
 * from the supplier/company name — the genuine junk is a small set of
 * placeholder values ("/", "No Brand", "OEM", "ODM/OEM", "Original"),
 * multi-brand slash/comma lists that end in one of those placeholders
 * ("FarmaMed/OEM", "SUOLI, KELIER, OEM"), and stray suffixes ("FOREVER
 * SCALES or Customized"). The factory-company-name stripping (Shenzhen/
 * Zhengzhou/.../Co.,Ltd/Technology Co/Trading Co) doesn't currently fire
 * on this corpus but is kept as real, defensive handling per Alibaba's
 * well-known convention, in case a future scrape's Brand Name field is
 * ever populated from the company name instead.
 */
function ft_clean_brand_name(?string $raw, string $categoryLabel): string
{
    $fallback = "Farmtech Pro-Series / {$categoryLabel}";
    $brand = trim((string) $raw);

    if ($brand === '') {
        return $fallback;
    }

    // Multi-brand slash lists ("Sonoscape/MEDSINGLONG", "FarmaMed/OEM") —
    // keep the first real (non-OEM/ODM) segment.
    $segments = array_filter(
        array_map('trim', explode('/', $brand)),
        fn ($s) => $s !== '' && !preg_match('/^(oem|odm)$/i', $s)
    );
    $brand = $segments ? (string) reset($segments) : '';

    // Trailing junk after a comma list ("SUOLI, KELIER, OEM" -> "SUOLI").
    if (str_contains($brand, ',')) {
        $brand = trim(explode(',', $brand)[0]);
    }

    // Factory/company-name boilerplate that sometimes leaks into a Brand
    // Name field on other listings, even though it doesn't on this corpus.
    $brand = preg_replace(
        '/\b(co\.,?\s*ltd\.?|co\.?\s*limited|technology\s*co\.?|trading\s*co\.?|industrial\s*co\.?|factory\s*direct\s*sale|factory|manufactory|manufacturer)\b/i',
        '',
        $brand
    );
    $brand = preg_replace(
        '/^(shenzhen|zhengzhou|guangzhou|dongguan|ningbo|yiwu|foshan|wenzhou|hangzhou|jinan|qingdao|xiamen|shanghai|beijing|shandong|henan|hebei)\s+/i',
        '',
        $brand
    );
    $brand = preg_replace('/\s+or\s+customi[sz]ed\s*$/i', '', $brand);
    $brand = trim(preg_replace('/\s+/', ' ', $brand), " \t\n\r\0\x0B,-/");

    $junkWhole = ['oem', 'odm', 'oem/odm', 'odm/oem', 'no brand', 'original', 'unbranded', 'n/a', 'none', '-'];
    if ($brand === '' || mb_strlen($brand) < 2 || in_array(strtolower($brand), $junkWhole, true)) {
        return $fallback;
    }

    return $brand;
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

    // Hunting-equipment rules go first — several of their keywords
    // ("thermal", "laser") overlap with construction-category terms
    // further down (thermal_diagnostics, laser_levels), so the more
    // specific hunting-context phrase has to win before the generic one
    // gets a chance to match.
    if (str_contains($lower, 'trail camera') || str_contains($lower, 'game camera') || str_contains($lower, 'scouting camera') || str_contains($lower, 'hunting camera')) {
        // Checked before the thermal/night-vision optics rule below —
        // "night vision"/"infrared" are common FEATURES a trail camera's
        // own listing advertises, not evidence the product itself is an
        // optic; the specific product-type phrase has to win.
        $category = 'game_trail_cameras';
        $hsCode = '8525.89';
    } elseif (
        (str_contains($lower, 'thermal') || str_contains($lower, 'night vision') || str_contains($lower, 'infrared'))
        && (str_contains($lower, 'monocular') || str_contains($lower, 'scope') || str_contains($lower, 'clip-on') || str_contains($lower, 'clip on') || str_contains($lower, 'riflescope') || str_contains($lower, 'hunting'))
    ) {
        $category = 'thermal_night_vision_optics';
        $hsCode = '9013.80';
    } elseif (str_contains($lower, 'game feeder') || str_contains($lower, 'deer feeder') || str_contains($lower, 'wildlife feeder') || str_contains($lower, 'feeder timer')) {
        $category = 'game_feeders';
        $hsCode = '8543.70';
    } elseif (str_contains($lower, 'rangefinder') || str_contains($lower, 'range finder') || str_contains($lower, 'ballistic')) {
        $category = 'rangefinders_ballistic';
        $hsCode = '9015.80';
    } elseif (str_contains($lower, 'radio collar') || str_contains($lower, 'tracking collar') || str_contains($lower, 'wildlife collar') || str_contains($lower, 'gps collar')) {
        $category = 'wildlife_tracking';
        $hsCode = '8526.91';
    } elseif (str_contains($lower, 'fuel') || str_contains($lower, 'diesel') || str_contains($lower, 'tank level') || str_contains($lower, 'flow meter')) {
        $category = 'fuel_monitoring';
        $hsCode = '9026.10';
    } elseif (str_contains($lower, 'thermal') || str_contains($lower, 'infrared camera') || str_contains($lower, 'imaging')) {
        $category = 'thermal_diagnostics';
        $hsCode = '9027.80';
    } elseif (str_contains($lower, 'laser')) {
        // Bare "level" alone used to qualify here too, which swallowed any
        // unrelated "water level"/"liquid level" sensor listing — laser is
        // the actual signal for this category (rotary/line laser levels).
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

/**
 * The stable numeric Alibaba listing id embedded in every product URL
 * (".../Some-Title_1601414781820.html?priceId=..."). The trailing
 * "?priceId=..." query param changes between scrapes of the exact same
 * listing (confirmed: two real re-scrapes of the same URL a session apart
 * had different priceId values) — comparing full source_url strings would
 * wrongly treat a rescrape as a brand-new, different product. This id is
 * what actually identifies "the same real listing" across scrapes.
 */
function ft_extract_alibaba_product_id(?string $url): ?string
{
    if ($url === null) {
        return null;
    }

    return preg_match('/_(\d{6,})\.html/', $url, $m) ? $m[1] : null;
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

    $paragraphs[] = '<p>All-in landed pricing — customs clearance and door-to-door delivery are handled for you, with nothing extra to pay on arrival. Sourced and vetted for the South African boer.</p>';

    return [
        'short_description' => $shortDescription,
        'description_html' => implode('', $paragraphs),
    ];
}
