import { test } from 'node:test';
import assert from 'node:assert/strict';
import { calculateLandedCost } from '../src/lib/landedCost.js';

test('matches the worked example from the spec formula', () => {
    // Base ZAR = (68 + 0.35*9.5) * 18.5 = (68 + 3.325) * 18.5 = 71.325 * 18.5 = 1319.5125
    // Landed  = (1319.5125 * 1.10) * 1.15 + 450 = 1451.46375 * 1.15 + 450 = 1669.183... + 450 = 2119.183...
    // Retail  = 2119.18 / (1 - 0.35) = 3260.28...
    const result = calculateLandedCost({
        supplierUsd: 68,
        weightKg: 0.35,
        usdZarRate: 18.5,
        dutyRate: 0.10,
        freightUsdPerKg: 9.5,
        vatRate: 0.15,
        clearingFeeZar: 450,
        targetMarginPct: 35,
    });

    assert.equal(result.base_zar, 1319.51);
    assert.equal(result.landed_cost_zar, 2119.18);
    assert.equal(result.retail_price_zar, 3260.28);
});

test('zero duty and zero weight collapse cleanly', () => {
    const result = calculateLandedCost({
        supplierUsd: 100,
        weightKg: 0,
        usdZarRate: 20,
        dutyRate: 0,
        freightUsdPerKg: 9.5,
        vatRate: 0.15,
        clearingFeeZar: 0,
        targetMarginPct: 0,
    });

    // Base = 100*20 = 2000; Landed = 2000*1*1.15 = 2300; Retail = Landed/1 = 2300
    assert.equal(result.base_zar, 2000);
    assert.equal(result.landed_cost_zar, 2300);
    assert.equal(result.retail_price_zar, 2300);
});

test('guards against margin >= 100% instead of dividing by zero or going negative', () => {
    const result = calculateLandedCost({
        supplierUsd: 50,
        weightKg: 1,
        usdZarRate: 18,
        dutyRate: 0.1,
        freightUsdPerKg: 9.5,
        vatRate: 0.15,
        clearingFeeZar: 100,
        targetMarginPct: 100,
    });

    assert.equal(result.retail_price_zar, result.landed_cost_zar);
    assert.ok(Number.isFinite(result.retail_price_zar));
});
