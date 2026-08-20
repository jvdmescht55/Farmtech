import { test } from 'node:test';
import assert from 'node:assert/strict';
import { evaluateValueDensity } from '../src/lib/valueDensityFilter.js';

test('passes a light, high-value item even though it would have failed the old flat $50 floor', () => {
    // A small, cheap sensor module — light enough that freight is a trivial
    // fraction of base cost, and margin at 38%+ still clears the R500 floor.
    const result = evaluateValueDensity({
        base_zar: 555.5, // $30 * 18.5
        intl_freight_zar: 9.25, // 0.03kg * $16/kg * 18.5 — ~1.6% of base, nowhere near the 35% threshold
        landed_cost_zar: 700,
        retail_price_zar: 1200, // net profit 500, right at the floor
    });

    assert.equal(result.passes, true);
});

test('rejects a heavy, low-value item on freight-to-base ratio despite technically-legal margin', () => {
    // Cast steel weights: heavy, cheap base cost, freight dominates.
    const result = evaluateValueDensity({
        base_zar: 500,
        intl_freight_zar: 300, // 60% of base — over the 35% threshold
        landed_cost_zar: 1200,
        retail_price_zar: 1800, // net profit 600 — clears R500 but not the R1500 exception
    });

    assert.equal(result.passes, false);
    assert.equal(result.reason, 'excessive_freight_ratio');
});

test('a high freight ratio is forgiven when net profit clears the R1500 exception', () => {
    const result = evaluateValueDensity({
        base_zar: 500,
        intl_freight_zar: 300, // still 60% of base
        landed_cost_zar: 1200,
        retail_price_zar: 3000, // net profit 1800 — clears the R1500 exception
    });

    assert.equal(result.passes, true);
});

test('rejects on minimum net profit even with a comfortable freight ratio', () => {
    const result = evaluateValueDensity({
        base_zar: 1000,
        intl_freight_zar: 50, // 5% of base, well under the 35% threshold
        landed_cost_zar: 1200,
        retail_price_zar: 1400, // net profit 200 — below the R500 floor
    });

    assert.equal(result.passes, false);
    assert.equal(result.reason, 'below_minimum_net_profit');
});

test('net profit is reported correctly on both pass and fail', () => {
    const passing = evaluateValueDensity({ base_zar: 1000, intl_freight_zar: 50, landed_cost_zar: 1200, retail_price_zar: 2000 });
    assert.equal(passing.netProfitZar, 800);

    const failing = evaluateValueDensity({ base_zar: 1000, intl_freight_zar: 50, landed_cost_zar: 1200, retail_price_zar: 1300 });
    assert.equal(failing.netProfitZar, 100);
});
