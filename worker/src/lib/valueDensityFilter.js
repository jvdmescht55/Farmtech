/**
 * Pure, fully-computable half of the Value-Density Feasibility Engine — the
 * checks that need no AI call (freight-to-base ratio, minimum net profit).
 * The AI-judged half (local SA dealer price competitiveness) lives in
 * geminiVetting.js/vettingPrompt.js since it genuinely needs the model.
 * Extracted from pipeline.js so it's unit-testable without mocking Gemini/DB.
 */

export const MIN_MARGIN_PCT = 38;
export const MIN_NET_PROFIT_ZAR = 500;
export const FREIGHT_TO_BASE_RATIO_THRESHOLD = 0.35;
export const FREIGHT_RATIO_PROFIT_EXCEPTION_ZAR = 1500;

/**
 * @param {{base_zar: number, intl_freight_zar: number, landed_cost_zar: number, retail_price_zar: number}} costing
 * @returns {{passes: boolean, reason: string|null, netProfitZar: number}}
 */
export function evaluateValueDensity(costing) {
    const netProfitZar = costing.retail_price_zar - costing.landed_cost_zar;

    // A heavy, low-value item (cast steel weights) where freight eats a
    // disproportionate share of the base cost is only worth importing if
    // the absolute profit is still substantial despite that. A light,
    // high-value item (electronics) never gets close to this ratio, so it
    // sails through regardless of the profit exception.
    if (costing.intl_freight_zar > costing.base_zar * FREIGHT_TO_BASE_RATIO_THRESHOLD && netProfitZar < FREIGHT_RATIO_PROFIT_EXCEPTION_ZAR) {
        return { passes: false, reason: 'excessive_freight_ratio', netProfitZar };
    }

    if (netProfitZar < MIN_NET_PROFIT_ZAR) {
        return { passes: false, reason: 'below_minimum_net_profit', netProfitZar };
    }

    return { passes: true, reason: null, netProfitZar };
}
