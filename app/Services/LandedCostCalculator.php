<?php

namespace App\Services;

/**
 * Mirrors worker/src/lib/landedCost.js — kept in sync deliberately so the
 * admin margin slider (PHP) and the sourcing pipeline (Node) compute
 * identical figures. See docs/superpowers/specs for the formula source.
 */
class LandedCostCalculator
{
    public function __construct(
        private readonly float $freightUsdPerKg,
        private readonly float $clearingFeeZar,
        private readonly float $vatRate,
    ) {}

    /**
     * @return array{base_zar: float, landed_cost_zar: float, retail_price_zar: float}
     */
    public function calculate(
        float $supplierUsd,
        float $weightKg,
        float $usdZarRate,
        float $dutyRate,
        float $targetMarginPct,
    ): array {
        $baseZar = ($supplierUsd + ($weightKg * $this->freightUsdPerKg)) * $usdZarRate;
        $landedCost = ($baseZar * (1 + $dutyRate)) * (1 + $this->vatRate) + $this->clearingFeeZar;

        $marginFraction = $targetMarginPct / 100;
        $retailPrice = $marginFraction < 1
            ? $landedCost / (1 - $marginFraction)
            : $landedCost; // guard against div-by-zero/negative on bad input

        return [
            'base_zar' => round($baseZar, 2),
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
