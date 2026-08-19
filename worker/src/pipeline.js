#!/usr/bin/env node
import 'dotenv/config';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { parseArgs, HELP_TEXT } from './lib/cli.js';
import { getRateWithFallback } from './lib/forex.js';
import { calculateLandedCost } from './lib/landedCost.js';
import { vetListing } from './lib/geminiVetting.js';
import { downloadAndOptimizeImages } from './lib/imagePipeline.js';
import { withRetry } from './lib/retry.js';
import {
    readFallbackRate, writeRate, isSupplierBlacklisted, getSetting, insertVettedProduct, closeConnection,
} from './lib/db.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const DRY_RUN_DEFAULTS = {
    target_margin_pct: Number(process.env.DEFAULT_TARGET_MARGIN_PCT ?? 35),
    air_freight_usd_per_kg: Number(process.env.DEFAULT_AIR_FREIGHT_USD_PER_KG ?? 9.5),
    clearing_agent_fee_zar: Number(process.env.DEFAULT_CLEARING_AGENT_FEE_ZAR ?? 450),
    vat_rate: Number(process.env.DEFAULT_VAT_RATE ?? 0.15),
    dry_run_usd_zar_rate: 18.5,
};

const REQUIRED_LISTING_FIELDS = [
    'sku', 'raw_title', 'category_hint', 'supplier_name',
    'supplier_price_usd', 'weight_kg', 'duty_rate', 'raw_specs_text',
];

async function main() {
    const args = parseArgs(process.argv.slice(2));

    if (args.help || !args.file) {
        console.log(HELP_TEXT);
        process.exitCode = args.help ? 0 : 1;
        return;
    }

    const listings = await loadListings(args.file, args.sku);
    console.log(`Loaded ${listings.length} listing(s) from ${args.file}${args.dryRun ? ' [DRY RUN]' : ''}\n`);

    const results = { passed: 0, warned: 0, failed: 0, errored: 0 };

    for (const listing of listings) {
        console.log(`── ${listing.sku}: ${listing.raw_title}`);

        try {
            await processListing(listing, args, results);
        } catch (err) {
            results.errored++;
            console.error(`  ERROR: ${err.message}\n`);
        }
    }

    console.log('─'.repeat(60));
    console.log(
        `Done. PASS/staged: ${results.passed}  WARN/staged: ${results.warned}  ` +
        `FAIL/rejected: ${results.failed}  errors: ${results.errored}`
    );

    if (!args.dryRun) {
        await closeConnection();
    }
}

async function loadListings(filePath, onlySku) {
    const raw = JSON.parse(await readFile(path.resolve(filePath), 'utf-8'));
    const listings = Array.isArray(raw) ? raw : [raw];

    for (const listing of listings) {
        for (const field of REQUIRED_LISTING_FIELDS) {
            if (listing[field] === undefined || listing[field] === null || listing[field] === '') {
                throw new Error(`Listing ${listing.sku ?? '(unknown sku)'} is missing required field "${field}"`);
            }
        }
    }

    return onlySku ? listings.filter((l) => l.sku === onlySku) : listings;
}

async function processListing(listing, args, results) {
    // 1. Supplier blacklist check — short-circuit before spending an AI call.
    if (!args.dryRun && await isSupplierBlacklisted(listing.supplier_name)) {
        console.log(`  SKIPPED — supplier "${listing.supplier_name}" is blacklisted.\n`);
        return;
    }

    // 2. Live forex with DB fallback (or a fixed dry-run rate).
    const { rate: usdZarRate, source: rateSource } = args.dryRun
        ? { rate: DRY_RUN_DEFAULTS.dry_run_usd_zar_rate, source: 'dry-run-fixed' }
        : await getRateWithFallback({
            apiKey: process.env.USD_ZAR_API_KEY,
            apiUrl: process.env.USD_ZAR_API_URL || 'https://v6.exchangerate-api.com/v6',
            readFallback: readFallbackRate,
            writeRate,
        });
    console.log(`  Forex: 1 USD = ${usdZarRate} ZAR (${rateSource})`);

    // 3. Landed cost settings (DB-driven in the admin, or config defaults in dry-run).
    const settings = args.dryRun
        ? DRY_RUN_DEFAULTS
        : {
            target_margin_pct: await getSetting('target_margin_pct', DRY_RUN_DEFAULTS.target_margin_pct),
            air_freight_usd_per_kg: await getSetting('air_freight_usd_per_kg', DRY_RUN_DEFAULTS.air_freight_usd_per_kg),
            clearing_agent_fee_zar: await getSetting('clearing_agent_fee_zar', DRY_RUN_DEFAULTS.clearing_agent_fee_zar),
            vat_rate: await getSetting('vat_rate', DRY_RUN_DEFAULTS.vat_rate),
        };

    const costing = calculateLandedCost({
        supplierUsd: listing.supplier_price_usd,
        weightKg: listing.weight_kg,
        usdZarRate,
        dutyRate: listing.duty_rate,
        freightUsdPerKg: settings.air_freight_usd_per_kg,
        vatRate: settings.vat_rate,
        clearingFeeZar: settings.clearing_agent_fee_zar,
        targetMarginPct: settings.target_margin_pct,
    });
    console.log(`  Landed cost: R${costing.landed_cost_zar}  →  Retail: R${costing.retail_price_zar}`);

    // 4. AI compliance vetting + copywriting (strict JSON via Gemini responseSchema).
    const vetting = await withRetry(
        () => vetListing(listing, { apiKey: process.env.GEMINI_API_KEY, model: process.env.GEMINI_MODEL || 'gemini-2.5-flash' }),
        { attempts: 3, baseDelayMs: 1500, label: `Gemini vetting for ${listing.sku}` }
    );
    console.log(`  Verdict: ${vetting.compliance.audit_verdict} (risk ${vetting.compliance.risk_score}/100) — ${vetting.product.category}/${vetting.product.hs_code}`);

    if (vetting.compliance.rejection_reasons?.length) {
        for (const reason of vetting.compliance.rejection_reasons) {
            console.log(`    ⚑ ${reason}`);
        }
    }

    // 5. Images — download, MIME-validate, convert to webp.
    let images = [];
    if (!args.dryRun && !args.skipImages && listing.image_urls?.length) {
        images = await downloadAndOptimizeImages(listing.image_urls, {
            uploadDir: process.env.WORKER_UPLOAD_DIR || '../public/uploads/products',
            sku: listing.sku,
        });
        console.log(`  Images: ${images.length}/${listing.image_urls.length} downloaded and converted to webp`);
    }

    const status = vetting.compliance.audit_verdict === 'FAIL' ? 'rejected' : 'pending_review';

    if (vetting.compliance.audit_verdict === 'PASS') results.passed++;
    else if (vetting.compliance.audit_verdict === 'WARN') results.warned++;
    else results.failed++;

    if (args.dryRun) {
        console.log(`  [DRY RUN] Would insert as status="${status}". Vetting result:`);
        console.log(JSON.stringify(vetting, null, 2));
        console.log('');
        return;
    }

    const { productId, slug } = await insertVettedProduct({
        sourced: {
            supplier_name: listing.supplier_name,
            supplier_years: listing.supplier_years,
            is_verified_supplier: listing.is_verified_supplier,
            has_trade_assurance: listing.has_trade_assurance,
            supplier_price_usd: listing.supplier_price_usd,
            weight_kg: listing.weight_kg,
            duty_rate: listing.duty_rate,
            stock_status: listing.stock_status,
            lead_time_days: listing.lead_time_days,
        },
        vetting,
        costing: { ...costing, vatRate: settings.vat_rate, targetMarginPct: settings.target_margin_pct },
        images,
        sku: listing.sku,
        status,
    });

    console.log(`  Inserted product #${productId} ("${slug}") with status="${status}"\n`);
}

main().catch((err) => {
    console.error('Fatal pipeline error:', err);
    process.exitCode = 1;
});
