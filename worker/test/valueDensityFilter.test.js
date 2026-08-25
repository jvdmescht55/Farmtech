import { test } from 'node:test';
import assert from 'node:assert/strict';
import { evaluateValueDensity, evaluateLogistics, parseDimensionsCm } from '../src/lib/valueDensityFilter.js';

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

// Logistics & Dimensional Gatekeeper

test('parseDimensionsCm extracts three numbers from varied supplier formats', () => {
    assert.deepEqual(parseDimensionsCm('28cm*14.7cm*6cm'), [28, 14.7, 6]);
    assert.deepEqual(parseDimensionsCm('50 x 40 x 30 cm'), [50, 40, 30]);
    assert.equal(parseDimensionsCm('single value'), null);
    assert.equal(parseDimensionsCm(null), null);
});

test('evaluateLogistics rejects a massive tractor generator over the 25kg weight limit', () => {
    const result = evaluateLogistics({ grossWeightKg: 180, packageDimensions: null, hazardText: 'Diesel generator', intlFreightZar: 1000, retailPriceZar: 20000 });
    assert.equal(result.passes, false);
    assert.equal(result.reason, 'exceeds_max_weight');
});

test('evaluateLogistics rejects an oversized crate on longest-side dimension', () => {
    const result = evaluateLogistics({ grossWeightKg: 20, packageDimensions: '150x40x40', hazardText: '', intlFreightZar: 500, retailPriceZar: 5000 });
    assert.equal(result.passes, false);
    assert.equal(result.reason, 'exceeds_max_dimension');
});

test('evaluateLogistics rejects on volumetric weight even when actual weight is under the limit', () => {
    // 100x100x100cm / 5000 = 200kg volumetric — light box, huge chargeable freight.
    const result = evaluateLogistics({ grossWeightKg: 5, packageDimensions: '100x100x100', hazardText: '', intlFreightZar: 500, retailPriceZar: 5000 });
    assert.equal(result.passes, false);
    assert.equal(result.reason, 'excessive_volumetric_weight');
});

test('evaluateLogistics rejects bulk hazardous goods regardless of weight/value', () => {
    const result = evaluateLogistics({ grossWeightKg: 2, packageDimensions: null, hazardText: 'Bulk Diesel Fuel Container 20L', intlFreightZar: 50, retailPriceZar: 2000 });
    assert.equal(result.passes, false);
    assert.equal(result.reason, 'prohibited_hazardous_goods');
});

test('evaluateLogistics rejects when shipping cost exceeds 65% of retail value', () => {
    const result = evaluateLogistics({ grossWeightKg: 22, packageDimensions: null, hazardText: '', intlFreightZar: 1400, retailPriceZar: 2000 });
    assert.equal(result.passes, false);
    assert.equal(result.reason, 'low_value_density');
});

test('evaluateLogistics passes a normal light, well-priced item', () => {
    const result = evaluateLogistics({ grossWeightKg: 1.5, packageDimensions: '30x20x15', hazardText: 'RFID ear tag reader', intlFreightZar: 100, retailPriceZar: 1500 });
    assert.equal(result.passes, true);
    assert.equal(result.reason, null);
});
