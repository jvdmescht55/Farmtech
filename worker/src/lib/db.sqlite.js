import { DatabaseSync } from 'node:sqlite';

/**
 * SQLite driver for environments without a MySQL/MariaDB server available
 * (e.g. this repo's own sandbox, or a quick local trial without Docker).
 * Mirrors db.mysql.js's exported interface exactly. Production/Docker still
 * uses MySQL by default — see docker-compose.yml and .env.example — this is
 * purely an additive fallback, selected via WORKER_DB_DRIVER=sqlite.
 */

let db;

function getDb() {
    if (!db) {
        const path = process.env.WORKER_SQLITE_PATH;
        if (!path) {
            throw new Error('WORKER_DB_DRIVER=sqlite requires WORKER_SQLITE_PATH to point at the Laravel app\'s database.sqlite file.');
        }
        db = new DatabaseSync(path);
        db.exec('PRAGMA foreign_keys = ON');
    }

    return db;
}

export async function closeConnection() {
    if (db) {
        db.close();
        db = undefined;
    }
}

export async function readFallbackRate(pair = 'USDZAR') {
    const row = getDb().prepare('SELECT rate FROM exchange_rates WHERE currency_pair = ? LIMIT 1').get(pair);
    return row ? Number(row.rate) : null;
}

export async function writeRate(rate, pair = 'USDZAR') {
    const now = sqlNow();
    getDb().prepare(
        `INSERT INTO exchange_rates (currency_pair, rate, updated_at) VALUES (?, ?, ?)
         ON CONFLICT(currency_pair) DO UPDATE SET rate = excluded.rate, updated_at = excluded.updated_at`
    ).run(pair, rate, now);
}

export async function isSupplierBlacklisted(supplierName) {
    const row = getDb().prepare('SELECT id FROM blacklisted_suppliers WHERE supplier_name = ? LIMIT 1').get(supplierName);
    return !!row;
}

export async function getSetting(key, fallback) {
    const row = getDb().prepare('SELECT value, type FROM settings WHERE key = ? LIMIT 1').get(key);

    if (!row) return fallback;

    switch (row.type) {
        case 'integer': return parseInt(row.value, 10);
        case 'decimal': return parseFloat(row.value);
        case 'boolean': return row.value === '1' || row.value === 'true';
        case 'json': return JSON.parse(row.value);
        default: return row.value;
    }
}

function slugify(title) {
    return title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function sqlNow() {
    return new Date().toISOString().slice(0, 19).replace('T', ' ');
}

export async function insertVettedProduct({ sourced, vetting, costing, images, sku, status }) {
    const conn = getDb();
    const now = sqlNow();

    conn.exec('BEGIN TRANSACTION');

    try {
        let slug = slugify(vetting.product.title);
        const existing = conn.prepare('SELECT id FROM products WHERE slug = ?').get(slug);
        if (existing) {
            slug = `${slug}-${Date.now().toString().slice(-5)}`;
        }

        const productResult = conn.prepare(
            `INSERT INTO products
                (sku, title, slug, category, short_description, description_html,
                 original_price_usd, est_weight_kg, hs_code, customs_duty_rate, vat_rate,
                 intl_freight_zar, customs_vat_zar, domestic_delivery_zar,
                 landed_cost_zar, retail_price_zar, profit_margin_pct,
                 stock_status, lead_time_days, status, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
        ).run(
            sku,
            vetting.product.title,
            slug,
            vetting.product.category,
            vetting.product.short_description,
            vetting.product.description_html,
            sourced.supplier_price_usd,
            sourced.weight_kg,
            vetting.product.hs_code,
            sourced.duty_rate,
            costing.vatRate,
            costing.intl_freight_zar,
            costing.customs_vat_zar,
            costing.domestic_delivery_zar,
            costing.landed_cost_zar,
            costing.retail_price_zar,
            costing.targetMarginPct,
            sourced.stock_status || 'pre_order',
            sourced.lead_time_days || '7-12 business days',
            status,
            0,
            now,
            now,
        );

        const productId = Number(productResult.lastInsertRowid);

        const specStmt = conn.prepare(
            `INSERT INTO product_specs (product_id, spec_group, spec_key, spec_value, is_highlight, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)`
        );
        for (const spec of vetting.product.specs) {
            specStmt.run(productId, spec.spec_group, spec.spec_key, spec.spec_value, spec.is_highlight ? 1 : 0, now, now);
        }

        const imageStmt = conn.prepare(
            `INSERT INTO product_images (product_id, original_url, local_path, is_thumbnail, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)`
        );
        images.forEach((image, i) => {
            imageStmt.run(productId, image.original_url, image.local_path, i === 0 ? 1 : 0, i, now, now);
        });

        conn.prepare(
            `INSERT INTO compliance_audits
                (product_id, supplier_name, supplier_years, is_verified_supplier, has_trade_assurance,
                 frequency_checked, icasa_status, plug_type_checked, battery_transport_cert,
                 risk_score, audit_verdict, rejection_reasons, raw_ai_analysis, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
        ).run(
            productId,
            sourced.supplier_name,
            sourced.supplier_years ?? null,
            sourced.is_verified_supplier ? 1 : 0,
            sourced.has_trade_assurance ? 1 : 0,
            vetting.compliance.frequency_checked,
            vetting.compliance.icasa_status,
            vetting.compliance.plug_type_checked ? 1 : 0,
            vetting.compliance.battery_transport_cert,
            vetting.compliance.risk_score,
            vetting.compliance.audit_verdict,
            JSON.stringify(vetting.compliance.rejection_reasons || []),
            JSON.stringify(vetting, null, 2),
            now,
            now,
        );

        conn.exec('COMMIT');

        return { productId, slug };
    } catch (err) {
        conn.exec('ROLLBACK');
        throw err;
    }
}
