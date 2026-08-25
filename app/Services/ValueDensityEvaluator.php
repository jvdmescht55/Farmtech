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
     * Logistics & Dimensional Gatekeeper — mirrors
     * worker/src/lib/valueDensityFilter.js's evaluateLogistics(), same
     * "PHP mirror of the Node enforcement point" relationship as evaluate()
     * above. Standard air-freight courier limits (a single ~25kg/120cm
     * parcel), not admin-tunable — a heavy machine or a drum of bulk liquid
     * simply cannot move as an air-freighted parcel, no matter the margin.
     */
    public const MAX_GROSS_WEIGHT_KG = 25.0;
    public const MAX_LONGEST_SIDE_CM = 120.0;
    public const VOLUMETRIC_DIVISOR = 5000.0;
    public const MAX_SHIPPING_TO_RETAIL_RATIO = 0.65;

    /** Substring match against title/specification text — deliberately broad; false positives here just mean a manual review, not a lost sale. */
    private const HAZARD_KEYWORDS = [
        'bulk diesel', 'pure diesel', 'bulk petrol', 'bulk fuel', 'bulk gasoline', 'kerosene',
        'lpg', 'propane', 'compressed gas', 'flammable liquid', 'flammable gas',
        'raw lithium cell', 'bare lithium cell', 'uncertified battery', 'loose battery cells',
        'explosive', 'corrosive acid', 'uncertified chemical', 'hazardous chemical', 'hazmat',
    ];

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

    /**
     * @param array{
     *     gross_weight_kg: ?float, package_dimensions: ?string, hazard_text: string,
     *     intl_freight_zar: ?float, retail_price_zar: ?float,
     * } $item
     * @return array{passes: bool, reason: ?string, detail: ?string}
     */
    public function evaluateLogistics(array $item): array
    {
        $weightKg = $item['gross_weight_kg'] !== null ? (float) $item['gross_weight_kg'] : null;
        $dims = $this->parseDimensionsCm($item['package_dimensions'] ?? null);

        if ($weightKg !== null && $weightKg > self::MAX_GROSS_WEIGHT_KG) {
            return [
                'passes' => false,
                'reason' => 'exceeds_max_weight',
                'detail' => sprintf('Auto-rejected: Exceeds air freight weight limit (>%gkg) — %.2fkg', self::MAX_GROSS_WEIGHT_KG, $weightKg),
            ];
        }

        if ($dims !== null) {
            $longestSideCm = max($dims);
            $volumetricWeightKg = ($dims[0] * $dims[1] * $dims[2]) / self::VOLUMETRIC_DIVISOR;

            if ($longestSideCm > self::MAX_LONGEST_SIDE_CM) {
                return [
                    'passes' => false,
                    'reason' => 'exceeds_max_dimension',
                    'detail' => sprintf('Auto-rejected: Longest side exceeds air freight parcel limit (>%gcm) — %.0fcm', self::MAX_LONGEST_SIDE_CM, $longestSideCm),
                ];
            }

            if ($volumetricWeightKg > self::MAX_GROSS_WEIGHT_KG) {
                return [
                    'passes' => false,
                    'reason' => 'excessive_volumetric_weight',
                    'detail' => sprintf('Auto-rejected: Volumetric weight (%.1fkg) exceeds air freight limit (>%gkg) — exorbitant chargeable freight', $volumetricWeightKg, self::MAX_GROSS_WEIGHT_KG),
                ];
            }
        }

        if ($this->containsHazardKeyword($item['hazard_text'] ?? '')) {
            return [
                'passes' => false,
                'reason' => 'prohibited_hazardous_goods',
                'detail' => 'Auto-rejected: Contains bulk liquid fuel, uncertified hazardous chemical, or raw/bulk combustible battery cells — cannot pass air customs clearing',
            ];
        }

        $freightZar = $item['intl_freight_zar'] !== null ? (float) $item['intl_freight_zar'] : null;
        $retailZar = $item['retail_price_zar'] !== null ? (float) $item['retail_price_zar'] : null;

        if ($freightZar !== null && $retailZar !== null && $retailZar > 0) {
            $ratio = $freightZar / $retailZar;

            if ($ratio > self::MAX_SHIPPING_TO_RETAIL_RATIO) {
                return [
                    'passes' => false,
                    'reason' => 'low_value_density',
                    'detail' => sprintf('Auto-rejected: Low value density / excessive shipping cost — freight is %.0f%% of retail value (>%.0f%% limit)', $ratio * 100, self::MAX_SHIPPING_TO_RETAIL_RATIO * 100),
                ];
            }
        }

        return ['passes' => true, 'reason' => null, 'detail' => null];
    }

    /**
     * Parses a free-text "LxWxH" package-dimension string in cm (format
     * varies by supplier — see ft_extract_package_dimensions() in
     * worker/lib_pricing.php, e.g. "28cm*14.7cm*6cm", "50 x 40 x 30 cm").
     * Returns null (never guesses/defaults a dimension) when fewer than 3
     * numbers can be parsed out.
     *
     * @return array{0: float, 1: float, 2: float}|null
     */
    public function parseDimensionsCm(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        preg_match_all('/(\d+(?:\.\d+)?)/', $raw, $matches);
        $numbers = array_map('floatval', $matches[1] ?? []);

        if (count($numbers) < 3) {
            return null;
        }

        return array_slice($numbers, 0, 3);
    }

    private function containsHazardKeyword(string $text): bool
    {
        $lower = strtolower($text);

        foreach (self::HAZARD_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
