<?php

namespace App\Services;

/**
 * Mirrors worker/src/lib/landedCost.js — kept in sync deliberately so the
 * admin margin slider (PHP) and the sourcing pipeline (Node) compute
 * identical figures. See that file's docblock for the formula and why
 * customs_vat_zar is an incremental (not cost-inclusive) amount.
 */
class LandedCostCalculator
{
    public function __construct(
        private readonly float $freightUsdPerKg,
        private readonly float $domesticDeliveryZar,
        private readonly float $vatRate,
    ) {}

    /**
     * @return array{base_zar: float, intl_freight_zar: float, customs_vat_zar: float, domestic_delivery_zar: float, landed_cost_zar: float, retail_price_zar: float}
     */
    public function calculate(
        float $supplierUsd,
        float $weightKg,
        float $usdZarRate,
        float $dutyRate,
        float $targetMarginPct,
    ): array {
        $baseZar = $supplierUsd * $usdZarRate;
        $intlFreightZar = $weightKg * $this->freightUsdPerKg * $usdZarRate;
        $dutiableZar = $baseZar + $intlFreightZar;
        $customsVatZar = $dutiableZar * ((1 + $dutyRate) * (1 + $this->vatRate) - 1);
        $landedCost = $baseZar + $intlFreightZar + $customsVatZar + $this->domesticDeliveryZar;

        $marginFraction = $targetMarginPct / 100;
        $retailPrice = $marginFraction < 1
            ? $landedCost / (1 - $marginFraction)
            : $landedCost; // guard against div-by-zero/negative on bad input

        return [
            'base_zar' => round($baseZar, 2),
            'intl_freight_zar' => round($intlFreightZar, 2),
            'customs_vat_zar' => round($customsVatZar, 2),
            'domestic_delivery_zar' => round($this->domesticDeliveryZar, 2),
            'landed_cost_zar' => round($landedCost, 2),
            'retail_price_zar' => round($retailPrice, 2),
        ];
    }

    public function marginPctFromRetail(float $landedCost, float $retailPrice): float
    {
        if ($retailPrice <= 0) {
            return 0.0;
        }

        return round((($retailPrice - $landedCost) / $retailPrice) * 100, 2);
    }
}
