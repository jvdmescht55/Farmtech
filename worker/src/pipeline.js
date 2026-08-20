#!/usr/bin/env node
import 'dotenv/config';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { parseArgs, HELP_TEXT } from './lib/cli.js';
import { getRateWithFallback } from './lib/forex.js';
import { calculateLandedCost } from './lib/landedCost.js';
import { evaluateValueDensity, MIN_MARGIN_PCT } from './lib/valueDensityFilter.js';
import { vetListing } from './lib/geminiVetting.js';
import { downloadAndOptimizeImages } from './lib/imagePipeline.js';
import { withRetry } from './lib/retry.js';
import {
    readFallbackRate, writeRate, isSupplierBlacklisted, getSetting, insertVettedProduct, closeConnection,
} from './lib/db.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const DRY_RUN_DEFAULTS = {
    target_margin_pct: Number(process.env.DEFAULT_TARGET_MARGIN_PCT ?? 35),
    air_freight_usd_per_kg: Number(process.env.DEFAULT_AIR_FREIGHT_USD_PER_KG ?? 16),
    clearing_agent_fee_zar: Number(process.env.DEFAULT_CLEARING_AGENT_FEE_ZAR ?? 250),
    vat_rate: Number(process.env.DEFAULT_VAT_RATE ?? 0.15),
    dry_run_usd_zar_rate: 18.5,
};

const REQUIRED_LISTING_FIELDS = [
    'sku', 'raw_title', 'category_hint', 'supplier_name',
    'supplier_price_usd', 'weight_kg', 'duty_rate', 'raw_specs_text',
];

// The Value-Density Feasibility Engine (see valueDensityFilter.js) —
// fixed platform policy, not admin-tunable via settings (unlike
// freight/VAT/delivery, which genuinely vary with real-world costs).
// Replaces a flat "$50 minimum" with a check that actually reasons about
// weight vs. value: a heavy, cheap item (cast steel weights) dies to
// freight cost same as before, but a light, high-value item under $50 (a
// small sensor module) no longer gets auto-rejected just for being
// inexpensive. The freight-ratio/min-profit half needs no AI call to
// reject on, same short-circuit principle as the supplier-blacklist check
// below. The pricing_verdict check (whether the price is competitive
// against SA dealer pricing) still needs the AI and runs after vetting.

async function main() {
    const args = parseArgs(process.argv.slice(2));

    if (args.help || !args.file) {
        console.log(HELP_TEXT);
        process.exitCode = args.help ? 0 : 1;
        return;
    }

    const listings = await loadListings(args.file, args.sku);
    console.log(`Loaded ${listings.length} listing(s) from ${args.file}${args.dryRun ? ' [DRY RUN]' : ''}\n`);

    const results = { passed: 0, warned: 0, failed: 0, uncompetitive: 0, errored: 0, items: [] };

    for (const listing of listings) {
        console.log(`── ${listing.sku}: ${listing.raw_title}`);

        try {
            const item = await processListing(listing, args, results);
            results.items.push(item);
        } catch (err) {
            results.errored++;
            results.items.push({ sku: listing.sku, title: listing.raw_title, error: err.message });
            console.error(`  ERROR: ${err.message}\n`);
        }
    }

    console.log('─'.repeat(60));
    console.log(
        `Done. PASS/staged: ${results.passed}  WARN/staged: ${results.warned}  ` +
        `FAIL/rejected: ${results.failed}  uncompetitive/rejected: ${results.uncompetitive}  errors: ${results.errored}`
    );

    // Machine-readable summary on its own line — callers that shell out to
    // this script (SourcingPipelineRunner) parse this instead of scraping
    // the human-readable log lines above, which are for the CLI/manual use.
    console.log('RESULT_JSON:'+JSON.stringify({
        counts: {
            passed: results.passed, warned: results.warned, failed: results.failed,
            uncompetitive: results.uncompetitive, errored: results.errored,
        },
        items: results.items,
    }));

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
        return { sku: listing.sku, title: listing.raw_title, skipped: true, reason: 'blacklisted_supplier' };
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

    // Margin floor: 38% minimum, over whatever the admin-configured target
    // margin is (never lower, can be higher).
    const effectiveMarginPct = Math.max(settings.target_margin_pct, MIN_MARGIN_PCT);

    const costing = calculateLandedCost({
        supplierUsd: listing.supplier_price_usd,
        weightKg: listing.weight_kg,
        usdZarRate,
        dutyRate: listing.duty_rate,
        freightUsdPerKg: settings.air_freight_usd_per_kg,
        vatRate: settings.vat_rate,
        domesticDeliveryZar: settings.clearing_agent_fee_zar,
        targetMarginPct: effectiveMarginPct,
    });
    const valueDensity = evaluateValueDensity(costing);
    console.log(`  Landed cost: R${costing.landed_cost_zar}  →  Required retail (${effectiveMarginPct}% margin): R${costing.retail_price_zar}  (net profit R${valueDensity.netProfitZar.toFixed(2)})`);

    // Value-Density Feasibility Engine — both checks fully computable
    // without an AI call, so reject a low-value-density item before paying
    // for vetting.
    if (!valueDensity.passes) {
        console.log(`  SKIPPED — ${valueDensity.reason} (net profit R${valueDensity.netProfitZar.toFixed(2)}, freight R${costing.intl_freight_zar} vs base R${costing.base_zar}).\n`);
        return { sku: listing.sku, title: listing.raw_title, skipped: true, reason: valueDensity.reason };
    }

    // 4. AI compliance vetting + copywriting (strict JSON via Gemini responseSchema),
    // handed the price this item would need to sell at so the model can judge
    // whether that's competitive against SA retail for the same tech spec.
    const vetting = await withRetry(
        () => vetListing(
            listing,
            { apiKey: process.env.GEMINI_API_KEY, model: process.env.GEMINI_MODEL || 'gemini-2.5-flash' },
            { required_retail_price_zar: costing.retail_price_zar, target_margin_pct: effectiveMarginPct },
        ),
        { attempts: 3, baseDelayMs: 1500, label: `Gemini vetting for ${listing.sku}` }
    );
    console.log(`  Verdict: ${vetting.compliance.audit_verdict} (risk ${vetting.compliance.risk_score}/100) — ${vetting.product.category}/${vetting.product.hs_code}`);
    console.log(`  Pricing: ${vetting.compliance.pricing_verdict}`);

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
            geminiApiKey: process.env.GEMINI_API_KEY,
            geminiImageModel: process.env.GEMINI_IMAGE_MODEL,
            geminiModel: process.env.GEMINI_MODEL,
        });
        console.log(`  Images: ${images.length}/${listing.image_urls.length} downloaded and converted to webp`);
    }

    // Compliance FAIL always wins (a non-compliant item is never "just a
    // pricing problem"). Otherwise, an AI-flagged uncompetitive price is its
    // own rejection reason — a technically-fine product a SA farmer could
    // buy cheaper locally isn't worth importing either.
    let status;
    if (vetting.compliance.audit_verdict === 'FAIL') {
        status = 'rejected';
        results.failed++;
    } else if (vetting.compliance.pricing_verdict === 'uncompetitive') {
        status = 'rejected_uncompetitive';
        results.uncompetitive++;
    } else if (vetting.compliance.audit_verdict === 'WARN') {
        status = 'pending_review';
        results.warned++;
    } else {
        status = 'pending_review';
        results.passed++;
    }

    if (args.dryRun) {
        console.log(`  [DRY RUN] Would insert as status="${status}". Vetting result:`);
        console.log(JSON.stringify(vetting, null, 2));
        console.log('');
        return {
            sku: listing.sku, title: vetting.product.title, verdict: vetting.compliance.audit_verdict, status, dryRun: true,
        };
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
        costing: { ...costing, vatRate: settings.vat_rate, targetMarginPct: effectiveMarginPct },
        images,
        sku: listing.sku,
        status,
    });

    console.log(`  Inserted product #${productId} ("${slug}") with status="${status}"\n`);

    return {
        sku: listing.sku, productId, slug, title: vetting.product.title, verdict: vetting.compliance.audit_verdict, status,
    };
}

main().catch((err) => {
    console.error('Fatal pipeline error:', err);
    process.exitCode = 1;
});
