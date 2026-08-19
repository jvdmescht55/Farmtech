import mysql from 'mysql2/promise';

let pool;

export function getPool() {
    if (!pool) {
        pool = mysql.createPool({
            host: process.env.WORKER_DB_HOST || process.env.DB_HOST || '127.0.0.1',
            port: Number(process.env.WORKER_DB_PORT || process.env.DB_PORT || 3306),
            database: process.env.WORKER_DB_DATABASE || process.env.DB_DATABASE || 'farmtech',
            user: process.env.WORKER_DB_USERNAME || process.env.DB_USERNAME || 'farmtech',
            password: process.env.WORKER_DB_PASSWORD || process.env.DB_PASSWORD || '',
            waitForConnections: true,
            connectionLimit: 5,
        });
    }

    return pool;
}

export async function closeConnection() {
    if (pool) await pool.end();
}

export async function readFallbackRate(pair = 'USDZAR') {
    const [rows] = await getPool().query('SELECT rate FROM exchange_rates WHERE currency_pair = ? LIMIT 1', [pair]);
    return rows.length ? Number(rows[0].rate) : null;
}

export async function writeRate(rate, pair = 'USDZAR') {
    await getPool().query(
        `INSERT INTO exchange_rates (currency_pair, rate, updated_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE rate = VALUES(rate), updated_at = NOW()`,
        [pair, rate]
    );
}

export async function isSupplierBlacklisted(supplierName) {
    const [rows] = await getPool().query('SELECT id FROM blacklisted_suppliers WHERE supplier_name = ? LIMIT 1', [supplierName]);
    return rows.length > 0;
}

export async function getSetting(key, fallback) {
    const [rows] = await getPool().query('SELECT value, type FROM settings WHERE `key` = ? LIMIT 1', [key]);

    if (!rows.length) return fallback;

    const { value, type } = rows[0];

    switch (type) {
        case 'integer': return parseInt(value, 10);
        case 'decimal': return parseFloat(value);
        case 'boolean': return value === '1' || value === 'true';
        case 'json': return JSON.parse(value);
        default: return value;
    }
}

function slugify(title) {
    return title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

/**
 * Inserts a fully-vetted listing (product + specs + images + compliance audit)
 * in a single transaction. `status` is 'pending_review' for anything the AI
 * didn't hard-FAIL, or 'rejected' for hard fails — the human admin makes the
 * final publish call either way (approve/reject in /admin), matching the
 * "staging queue" design: the pipeline never auto-publishes.
 */
export async function insertVettedProduct({ sourced, vetting, costing, images, sku, status }) {
    const conn = await getPool().getConnection();

    try {
        await conn.beginTransaction();

        let slug = slugify(vetting.product.title);
        const [existing] = await conn.query('SELECT id FROM products WHERE slug = ?', [slug]);
        if (existing.length) {
            slug = `${slug}-${Date.now().toString().slice(-5)}`;
        }

        const [productResult] = await conn.query(
            `INSERT INTO products
                (sku, title, slug, category, short_description, description_html,
                 original_price_usd, est_weight_kg, hs_code, customs_duty_rate, vat_rate,
                 landed_cost_zar, retail_price_zar, profit_margin_pct,
                 stock_status, lead_time_days, status, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
            [
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
                costing.landed_cost_zar,
                costing.retail_price_zar,
                costing.targetMarginPct,
                sourced.stock_status || 'pre_order',
                sourced.lead_time_days || '7-12 business days',
                status,
                false,
            ]
        );

        const productId = productResult.insertId;

        for (const spec of vetting.product.specs) {
            await conn.query(
                `INSERT INTO product_specs (product_id, spec_group, spec_key, spec_value, is_highlight, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())`,
                [productId, spec.spec_group, spec.spec_key, spec.spec_value, !!spec.is_highlight]
            );
        }

        for (const [i, image] of images.entries()) {
            await conn.query(
                `INSERT INTO product_images (product_id, original_url, local_path, is_thumbnail, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())`,
                [productId, image.original_url, image.local_path, i === 0, i]
            );
        }

        await conn.query(
            `INSERT INTO compliance_audits
                (product_id, supplier_name, supplier_years, is_verified_supplier, has_trade_assurance,
                 frequency_checked, icasa_status, plug_type_checked, battery_transport_cert,
                 risk_score, audit_verdict, rejection_reasons, raw_ai_analysis, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
            [
                productId,
                sourced.supplier_name,
                sourced.supplier_years ?? null,
                !!sourced.is_verified_supplier,
                !!sourced.has_trade_assurance,
                vetting.compliance.frequency_checked,
                vetting.compliance.icasa_status,
                !!vetting.compliance.plug_type_checked,
                vetting.compliance.battery_transport_cert,
                vetting.compliance.risk_score,
                vetting.compliance.audit_verdict,
                JSON.stringify(vetting.compliance.rejection_reasons || []),
                JSON.stringify(vetting, null, 2),
            ]
        );

        await conn.commit();

        return { productId, slug };
    } catch (err) {
        await conn.rollback();
        throw err;
    } finally {
        conn.release();
    }
}
