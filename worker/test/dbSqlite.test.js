import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, copyFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const REAL_DB = path.resolve(__dirname, '..', '..', 'database', 'database.sqlite');

// These tests run against a throwaway copy of the actual Laravel-migrated
// database.sqlite (not the live one the dev server uses), so they only
// prove the sqlite driver works if that file exists and has been migrated —
// skip gracefully otherwise instead of failing CI environments that never
// booted the PHP app.
async function withTestDb(t) {
    let hasRealDb = true;
    try {
        await copyFile(REAL_DB, REAL_DB); // existence probe
    } catch {
        hasRealDb = false;
    }

    if (!hasRealDb) {
        t.skip('database/database.sqlite not found — run `php artisan migrate` first');
        return null;
    }

    const dir = await mkdtemp(path.join(tmpdir(), 'farmtech-sqlite-'));
    const dbPath = path.join(dir, 'test.sqlite');
    await copyFile(REAL_DB, dbPath);
    t.after(async () => {
        process.env.WORKER_SQLITE_PATH = undefined;
        await rm(dir, { recursive: true, force: true });
    });

    process.env.WORKER_DB_DRIVER = 'sqlite';
    process.env.WORKER_SQLITE_PATH = dbPath;

    // Force a fresh module load bound to this test's WORKER_SQLITE_PATH.
    const mod = await import(`../src/lib/db.sqlite.js?t=${Date.now()}`);
    return mod;
}

test('sqlite driver: settings round-trip matches the mysql driver contract', async (t) => {
    const db = await withTestDb(t);
    if (!db) return;

    const margin = await db.getSetting('target_margin_pct', null);
    assert.equal(typeof margin, 'number');

    const missing = await db.getSetting('does_not_exist', 'fallback-value');
    assert.equal(missing, 'fallback-value');

    await db.closeConnection();
});

test('sqlite driver: forex fallback read/write round-trips', async (t) => {
    const db = await withTestDb(t);
    if (!db) return;

    const seeded = await db.readFallbackRate('USDZAR');
    assert.ok(seeded === null || typeof seeded === 'number');

    await db.writeRate(19.42, 'USDZAR');
    const updated = await db.readFallbackRate('USDZAR');
    assert.equal(updated, 19.42);

    await db.closeConnection();
});

test('sqlite driver: insertVettedProduct writes product + specs + images + compliance audit', async (t) => {
    const db = await withTestDb(t);
    if (!db) return;

    const notBlacklisted = await db.isSupplierBlacklisted('Some Totally Fine Supplier Ltd');
    assert.equal(notBlacklisted, false);

    const { productId, slug } = await db.insertVettedProduct({
        sourced: {
            supplier_name: 'Test Supplier Co',
            supplier_years: 5,
            is_verified_supplier: true,
            has_trade_assurance: true,
            supplier_price_usd: 50,
            weight_kg: 0.5,
            duty_rate: 0.1,
            stock_status: 'in_stock',
            lead_time_days: '7-12 business days',
        },
        vetting: {
            product: {
                title: 'Test RFID Reader For Unit Tests',
                short_description: 'A short description.',
                description_html: '<p>Details.</p>',
                category: 'rfid',
                hs_code: '8471.90',
                specs: [
                    { spec_group: 'Frequency & Compliance', spec_key: 'Frequency', spec_value: '134.2 kHz', is_highlight: true },
                ],
            },
            compliance: {
                frequency_checked: '134.2 kHz ISO 11784/5 compliant',
                icasa_status: 'exempt',
                plug_type_checked: true,
                battery_transport_cert: 'UN38.3',
                risk_score: 12,
                audit_verdict: 'PASS',
                rejection_reasons: [],
            },
        },
        costing: {
            intl_freight_zar: 148, customs_vat_zar: 172.5, domestic_delivery_zar: 250,
            landed_cost_zar: 1000, retail_price_zar: 1538.46, vatRate: 0.15, targetMarginPct: 35,
        },
        images: [{ original_url: 'https://example.com/a.jpg', local_path: 'TEST-1.webp' }],
        sku: `TEST-SKU-${Date.now()}`,
        status: 'pending_review',
    });

    assert.ok(productId > 0);
    assert.match(slug, /test-rfid-reader-for-unit-tests/);

    await db.closeConnection();
});
