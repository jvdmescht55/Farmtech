/**
 * Mirrors app/Services/LandedCostCalculator.php — kept in sync deliberately
 * so the pipeline (Node) and the admin margin slider (PHP) compute
 * identical figures. See docs/superpowers/specs for the formula source.
 *
 *   Base ZAR      = (Supplier USD + (Weight KG * Freight USD/KG)) * USDZAR
 *   Landed Cost   = (Base ZAR * (1 + Duty Rate)) * (1 + VAT Rate) + Clearance Fee
 *   Retail Price  = Landed Cost / (1 - Target Margin)
 */

export function calculateLandedCost({
    supplierUsd,
    weightKg,
    usdZarRate,
    dutyRate,
    freightUsdPerKg,
    vatRate,
    clearingFeeZar,
    targetMarginPct,
}) {
    const baseZar = (supplierUsd + weightKg * freightUsdPerKg) * usdZarRate;
    const landedCostZar = baseZar * (1 + dutyRate) * (1 + vatRate) + clearingFeeZar;

    const marginFraction = targetMarginPct / 100;
    const retailPriceZar = marginFraction < 1
        ? landedCostZar / (1 - marginFraction)
        : landedCostZar; // guard against div-by-zero/negative on bad input

    return {
        base_zar: round2(baseZar),
        landed_cost_zar: round2(landedCostZar),
        retail_price_zar: round2(retailPriceZar),
    };
}

function round2(n) {
    return Math.round(n * 100) / 100;
}
