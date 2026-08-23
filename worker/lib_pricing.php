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

function ft_compute_costing(float $usdPrice, float $weightKg, float $forex): array
{
    $intlFreightZar = round($weightKg * 250.00, 2);
    $customsDutyZar = round(($usdPrice * $forex) * 0.10, 2);
    $vatZar = round((($usdPrice * $forex) + $intlFreightZar + $customsDutyZar) * 0.15, 2);
    $domesticZar = 250.00;
    $landedCost = round(($usdPrice * $forex) + $intlFreightZar + $customsDutyZar + $vatZar + $domesticZar, 2);
    $retailPrice = round($landedCost / (1 - 0.38), 2);

    return compact('intlFreightZar', 'customsDutyZar', 'vatZar', 'domesticZar', 'landedCost', 'retailPrice');
}
