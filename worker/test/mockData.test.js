import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { VETTING_RESPONSE_SCHEMA, buildUserMessage } from '../src/prompts/vettingPrompt.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const REQUIRED_LISTING_FIELDS = [
    'sku', 'raw_title', 'category_hint', 'supplier_name',
    'supplier_price_usd', 'weight_kg', 'duty_rate', 'raw_specs_text',
];
const VALID_CATEGORIES = ['scales', 'ultrasound', 'rfid', 'accessories', 'fencing', 'solar_pumps'];

test('mock_data.json has exactly the 5 spec-required test cases with all required fields', async () => {
    const raw = await readFile(path.join(__dirname, '..', 'mock_data.json'), 'utf-8');
    const listings = JSON.parse(raw);

    assert.equal(listings.length, 5);

    const skus = new Set();
    for (const listing of listings) {
        for (const field of REQUIRED_LISTING_FIELDS) {
            assert.ok(
                listing[field] !== undefined && listing[field] !== null && listing[field] !== '',
                `listing ${listing.sku} missing field "${field}"`
            );
        }
        assert.ok(VALID_CATEGORIES.includes(listing.category_hint), `invalid category_hint for ${listing.sku}`);
        assert.ok(Array.isArray(listing.image_urls), `image_urls must be an array for ${listing.sku}`);
        skus.add(listing.sku);
    }

    assert.equal(skus.size, 5, 'SKUs must be unique');

    // Sanity-check the specific 5 scenarios the spec calls for are represented.
    const bySku = Object.fromEntries(listings.map((l) => [l.sku, l]));
    assert.match(bySku['FT-RFID-STICK-134K'].raw_specs_text, /134\.2/, 'valid ISO reader should mention 134.2kHz');
    assert.match(bySku['FT-USND-CATTLE-REC'].raw_specs_text, /rectal/i, 'ultrasound case should specify a rectal probe');
    assert.match(bySku['FT-RFID-125K-INVALID'].raw_specs_text, /125/, 'invalid reader should mention 125kHz');
    assert.match(bySku['FT-SCALE-T7E-IND'].raw_specs_text, /mV\/V/, 'scale indicator should mention load cell sensitivity');
    assert.equal(bySku['FT-USND-RISK-UNVERIFIED'].is_verified_supplier, false, 'high-risk case should be an unverified supplier');
});

test('buildUserMessage embeds the full listing as JSON for the model', () => {
    const listing = { sku: 'X', raw_title: 'Test' };
    const message = buildUserMessage(listing);
    assert.match(message, /"sku": "X"/);
    assert.match(message, /JSON only/);
});

test('VETTING_RESPONSE_SCHEMA requires the DB-mapped fields on both product and compliance', () => {
    assert.deepEqual(
        VETTING_RESPONSE_SCHEMA.required.sort(),
        ['compliance', 'product'].sort()
    );
    assert.deepEqual(
        VETTING_RESPONSE_SCHEMA.properties.compliance.required.sort(),
        ['audit_verdict', 'battery_transport_cert', 'frequency_checked', 'icasa_status', 'local_price_delta_pct', 'plug_type_checked', 'pricing_verdict', 'rejection_reasons', 'risk_score'].sort()
    );
    assert.deepEqual(
        VETTING_RESPONSE_SCHEMA.properties.compliance.properties.audit_verdict.enum,
        ['PASS', 'WARN', 'FAIL']
    );
    assert.deepEqual(
        VETTING_RESPONSE_SCHEMA.properties.compliance.properties.pricing_verdict.enum,
        ['competitive', 'uncompetitive']
    );
});
