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

// Logistics & Dimensional Gatekeeper — mirrors
// App\Services\ValueDensityEvaluator::evaluateLogistics() (PHP), same
// principle as the rest of this file's relationship to that class. Standard
// air-freight courier limits, not admin-tunable.
export const MAX_GROSS_WEIGHT_KG = 25;
export const MAX_LONGEST_SIDE_CM = 120;
export const VOLUMETRIC_DIVISOR = 5000;
export const MAX_SHIPPING_TO_RETAIL_RATIO = 0.65;

const HAZARD_KEYWORDS = [
    'bulk diesel', 'pure diesel', 'bulk petrol', 'bulk fuel', 'bulk gasoline', 'kerosene',
    'lpg', 'propane', 'compressed gas', 'flammable liquid', 'flammable gas',
    'raw lithium cell', 'bare lithium cell', 'uncertified battery', 'loose battery cells',
    'explosive', 'corrosive acid', 'uncertified chemical', 'hazardous chemical', 'hazmat',
];

/** Free-text "LxWxH" cm string -> [L, W, H] in cm, or null if fewer than 3 numbers parse out. */
export function parseDimensionsCm(raw) {
    if (!raw || typeof raw !== 'string') return null;

    const numbers = (raw.match(/\d+(?:\.\d+)?/g) || []).map(Number);
    if (numbers.length < 3) return null;

    return numbers.slice(0, 3);
}

function containsHazardKeyword(text) {
    const lower = (text || '').toLowerCase();
    return HAZARD_KEYWORDS.some((kw) => lower.includes(kw));
}

/**
 * @param {{
 *   grossWeightKg?: number|null, packageDimensions?: string|null, hazardText?: string,
 *   intlFreightZar?: number|null, retailPriceZar?: number|null,
 * }} item
 * @returns {{passes: boolean, reason: string|null, detail: string|null}}
 */
export function evaluateLogistics(item) {
    const weightKg = item.grossWeightKg ?? null;
    const dims = parseDimensionsCm(item.packageDimensions ?? null);

    if (weightKg !== null && weightKg > MAX_GROSS_WEIGHT_KG) {
        return {
            passes: false,
            reason: 'exceeds_max_weight',
            detail: `Auto-rejected: Exceeds air freight weight limit (>${MAX_GROSS_WEIGHT_KG}kg) — ${weightKg.toFixed(2)}kg`,
        };
    }

    if (dims) {
        const longestSideCm = Math.max(...dims);
        const volumetricWeightKg = (dims[0] * dims[1] * dims[2]) / VOLUMETRIC_DIVISOR;

        if (longestSideCm > MAX_LONGEST_SIDE_CM) {
            return {
                passes: false,
                reason: 'exceeds_max_dimension',
                detail: `Auto-rejected: Longest side exceeds air freight parcel limit (>${MAX_LONGEST_SIDE_CM}cm) — ${longestSideCm.toFixed(0)}cm`,
            };
        }

        if (volumetricWeightKg > MAX_GROSS_WEIGHT_KG) {
            return {
                passes: false,
                reason: 'excessive_volumetric_weight',
                detail: `Auto-rejected: Volumetric weight (${volumetricWeightKg.toFixed(1)}kg) exceeds air freight limit (>${MAX_GROSS_WEIGHT_KG}kg) — exorbitant chargeable freight`,
            };
        }
    }

    if (containsHazardKeyword(item.hazardText)) {
        return {
            passes: false,
            reason: 'prohibited_hazardous_goods',
            detail: 'Auto-rejected: Contains bulk liquid fuel, uncertified hazardous chemical, or raw/bulk combustible battery cells — cannot pass air customs clearing',
        };
    }

    const freightZar = item.intlFreightZar ?? null;
    const retailZar = item.retailPriceZar ?? null;

    if (freightZar !== null && retailZar !== null && retailZar > 0) {
        const ratio = freightZar / retailZar;

        if (ratio > MAX_SHIPPING_TO_RETAIL_RATIO) {
            return {
                passes: false,
                reason: 'low_value_density',
                detail: `Auto-rejected: Low value density / excessive shipping cost — freight is ${(ratio * 100).toFixed(0)}% of retail value (>${(MAX_SHIPPING_TO_RETAIL_RATIO * 100).toFixed(0)}% limit)`,
            };
        }
    }

    return { passes: true, reason: null, detail: null };
}

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
