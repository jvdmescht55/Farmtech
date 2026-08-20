import { test } from 'node:test';
import assert from 'node:assert/strict';
import { calculateLandedCost } from '../src/lib/landedCost.js';

test('matches the worked example from the spec formula', () => {
    // Base ZAR      = 68 * 18.5 = 1258
    // Intl Freight  = (0.35 * 16) * 18.5 = 103.6
    // Customs+VAT   = (1258 + 103.6) * (1.10*1.15 - 1) = 1361.6 * 0.265 = 360.824
    // Landed        = 1258 + 103.6 + 360.824 + 250 = 1972.424
    // Retail        = 1972.424 / (1 - 0.35) = 3034.498... -> 3034.50
    const result = calculateLandedCost({
        supplierUsd: 68,
        weightKg: 0.35,
        usdZarRate: 18.5,
        dutyRate: 0.10,
        freightUsdPerKg: 16,
        vatRate: 0.15,
        domesticDeliveryZar: 250,
        targetMarginPct: 35,
    });

    assert.equal(result.base_zar, 1258);
    assert.equal(result.intl_freight_zar, 103.6);
    assert.equal(result.customs_vat_zar, 360.82);
    assert.equal(result.domestic_delivery_zar, 250);
    assert.equal(result.landed_cost_zar, 1972.42);
    assert.equal(result.retail_price_zar, 3034.5);

    // The four line items must sum to the landed cost — no double-counting
    // base+freight inside customs_vat_zar (see the module docblock).
    const sum = result.base_zar + result.intl_freight_zar + result.customs_vat_zar + result.domestic_delivery_zar;
    assert.ok(Math.abs(sum - result.landed_cost_zar) < 0.01);
});

test('zero duty and zero weight collapse cleanly', () => {
    const result = calculateLandedCost({
        supplierUsd: 100,
        weightKg: 0,
        usdZarRate: 20,
        dutyRate: 0,
        freightUsdPerKg: 16,
        vatRate: 0.15,
        domesticDeliveryZar: 0,
        targetMarginPct: 0,
    });

    // Base = 100*20 = 2000; freight = 0; customs+VAT = 2000*0.15 = 300; landed = 2300; retail = 2300
    assert.equal(result.base_zar, 2000);
    assert.equal(result.intl_freight_zar, 0);
    assert.equal(result.customs_vat_zar, 300);
    assert.equal(result.landed_cost_zar, 2300);
    assert.equal(result.retail_price_zar, 2300);
});

test('guards against margin >= 100% instead of dividing by zero or going negative', () => {
    const result = calculateLandedCost({
        supplierUsd: 50,
        weightKg: 1,
        usdZarRate: 18,
        dutyRate: 0.1,
        freightUsdPerKg: 16,
        vatRate: 0.15,
        domesticDeliveryZar: 100,
        targetMarginPct: 100,
    });

    assert.equal(result.retail_price_zar, result.landed_cost_zar);
    assert.ok(Number.isFinite(result.retail_price_zar));
});
