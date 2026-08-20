<?php

namespace App\Services;

/**
 * Mirrors worker/src/lib/valueDensityFilter.js — kept in sync deliberately,
 * same principle as LandedCostCalculator. The Node pipeline is the actual
 * enforcement point (it runs this before every AI vetting call); this PHP
 * mirror exists so the admin margin-slider breakdown can show the same
 * verdict without needing a second, drifting implementation.
 */
class ValueDensityEvaluator
{
    public const MIN_NET_PROFIT_ZAR = 500.0;
    public const FREIGHT_TO_BASE_RATIO_THRESHOLD = 0.35;
    public const FREIGHT_RATIO_PROFIT_EXCEPTION_ZAR = 1500.0;

    /**
     * @param array{base_zar: float, intl_freight_zar: float, landed_cost_zar: float, retail_price_zar: float} $costing
     * @return array{passes: bool, reason: ?string, net_profit_zar: float}
     */
    public function evaluate(array $costing): array
    {
        $netProfitZar = $costing['retail_price_zar'] - $costing['landed_cost_zar'];

        if ($costing['intl_freight_zar'] > $costing['base_zar'] * self::FREIGHT_TO_BASE_RATIO_THRESHOLD
            && $netProfitZar < self::FREIGHT_RATIO_PROFIT_EXCEPTION_ZAR) {
            return ['passes' => false, 'reason' => 'excessive_freight_ratio', 'net_profit_zar' => round($netProfitZar, 2)];
        }

        if ($netProfitZar < self::MIN_NET_PROFIT_ZAR) {
            return ['passes' => false, 'reason' => 'below_minimum_net_profit', 'net_profit_zar' => round($netProfitZar, 2)];
        }

        return ['passes' => true, 'reason' => null, 'net_profit_zar' => round($netProfitZar, 2)];
    }
}
