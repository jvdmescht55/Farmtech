/**
 * Mirrors app/Services/LandedCostCalculator.php — kept in sync deliberately
 * so the pipeline (Node) and the admin margin slider (PHP) compute
 * identical figures. See docs/superpowers/specs for the formula source.
 *
 *   Base ZAR             = Supplier USD * USD/ZAR
 *   Intl Freight ZAR      = (Weight KG * Freight USD/KG) * USD/ZAR
 *   Customs + VAT ZAR     = (Base ZAR + Intl Freight ZAR) * [(1 + Duty Rate) * (1 + VAT Rate) - 1]
 *   Domestic Delivery ZAR = flat allowance (courier + clearance, admin-configurable)
 *   Landed Cost           = Base ZAR + Intl Freight ZAR + Customs + VAT ZAR + Domestic Delivery ZAR
 *   Retail Price          = Landed Cost / (1 - Target Margin)
 *
 * customs_vat_zar is the INCREMENTAL duty+VAT amount — not (base+freight)*(1+duty)*(1+vat)
 * outright, which would double-count base+freight once directly and once again inside that
 * product when summed into landed_cost. The `- 1` subtracts the principal back out so the four
 * line items sum to exactly the same landed cost the old single-shot formula produced (just with
 * freight and tax now broken out for the admin "Profit Breakdown" UI), not a larger number.
 */

export function calculateLandedCost({
    supplierUsd,
    weightKg,
    usdZarRate,
    dutyRate,
    freightUsdPerKg,
    vatRate,
    domesticDeliveryZar,
    targetMarginPct,
}) {
    const baseZar = supplierUsd * usdZarRate;
    const intlFreightZar = weightKg * freightUsdPerKg * usdZarRate;
    const dutiableZar = baseZar + intlFreightZar;
    const customsVatZar = dutiableZar * ((1 + dutyRate) * (1 + vatRate) - 1);
    const landedCostZar = baseZar + intlFreightZar + customsVatZar + domesticDeliveryZar;

    const marginFraction = targetMarginPct / 100;
    const retailPriceZar = marginFraction < 1
        ? landedCostZar / (1 - marginFraction)
        : landedCostZar; // guard against div-by-zero/negative on bad input

    return {
        base_zar: round2(baseZar),
        intl_freight_zar: round2(intlFreightZar),
        customs_vat_zar: round2(customsVatZar),
        domestic_delivery_zar: round2(domesticDeliveryZar),
        landed_cost_zar: round2(landedCostZar),
        retail_price_zar: round2(retailPriceZar),
    };
}

function round2(n) {
    return Math.round(n * 100) / 100;
}
