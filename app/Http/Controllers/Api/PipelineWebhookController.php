<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessScrapedBatchJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Batch ingestion endpoint for scheduled scrapers (Apify or similar) —
 * accepts an array of raw supplier listings, maps each one into the same
 * internal shape worker/src/pipeline.js already expects (the shape an
 * admin's manual "Source New Listing" submission produces), and hands the
 * whole batch to a queued job. Never runs AI vetting inline on the request —
 * a batch could be dozens of Gemini calls deep, which has no business
 * happening inside an HTTP request/response cycle.
 *
 * category_hint and duty_rate are required even though they weren't in the
 * scraper's raw fields (title/price_usd/weight_kg/specs_table/images/
 * supplier_meta) — the landed-cost calculation needs a real SA import duty
 * rate before AI vetting even runs, and nothing in this codebase invents
 * one. A scraper config assigns both per source/category, the same way an
 * admin already does by hand on the manual form.
 */
class PipelineWebhookController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1', 'max:200'],
            'products.*.sku' => ['nullable', 'string', 'max:64'],
            'products.*.title' => ['required', 'string', 'max:255'],
            'products.*.category_hint' => ['required', Rule::enum(ProductCategory::class)],
            'products.*.price_usd' => ['required', 'numeric', 'min:0'],
            'products.*.weight_kg' => ['required', 'numeric', 'min:0'],
            'products.*.duty_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'products.*.specs_table' => ['required'],
            'products.*.images' => ['nullable', 'array'],
            'products.*.images.*' => ['url'],
            'products.*.supplier_meta' => ['required', 'array'],
            'products.*.supplier_meta.name' => ['required', 'string', 'max:255'],
            'products.*.supplier_meta.years' => ['nullable', 'integer', 'min:0'],
            'products.*.supplier_meta.is_verified' => ['nullable', 'boolean'],
            'products.*.supplier_meta.has_trade_assurance' => ['nullable', 'boolean'],
            'products.*.stock_status' => ['nullable', 'in:in_stock,pre_order'],
            'products.*.lead_time_days' => ['nullable', 'string', 'max:100'],
        ]);

        $listings = array_map([$this, 'mapToListing'], $validated['products']);

        ProcessScrapedBatchJob::dispatch($listings);

        return response()->json([
            'queued' => count($listings),
            'message' => count($listings).' product(s) queued for AI vetting and landed-cost calculation.',
        ], 202);
    }

    private function mapToListing(array $raw): array
    {
        return [
            'sku' => $raw['sku'] ?? 'SCR-'.Str::upper(Str::random(6)).'-'.now()->format('mdHi'),
            'raw_title' => $raw['title'],
            'category_hint' => $raw['category_hint'],
            'supplier_name' => $raw['supplier_meta']['name'],
            'supplier_years' => $raw['supplier_meta']['years'] ?? null,
            'is_verified_supplier' => (bool) ($raw['supplier_meta']['is_verified'] ?? false),
            'has_trade_assurance' => (bool) ($raw['supplier_meta']['has_trade_assurance'] ?? false),
            'supplier_price_usd' => (float) $raw['price_usd'],
            'weight_kg' => (float) $raw['weight_kg'],
            'duty_rate' => (float) $raw['duty_rate'],
            'stock_status' => $raw['stock_status'] ?? 'in_stock',
            'lead_time_days' => $raw['lead_time_days'] ?? config('farmtech.lead_time_default'),
            'raw_specs_text' => $this->flattenSpecs($raw['specs_table']),
            'image_urls' => array_values($raw['images'] ?? []),
        ];
    }

    /** Vetting prompt expects a plain text blob — flatten a scraper's structured spec table into one. */
    private function flattenSpecs(mixed $specs): string
    {
        if (is_string($specs)) {
            return $specs;
        }

        if (is_array($specs)) {
            $lines = [];
            foreach ($specs as $key => $value) {
                $label = is_string($key) ? $key : null;
                $lines[] = $label ? "{$label}: {$value}." : "{$value}.";
            }

            return implode(' ', $lines);
        }

        return (string) $specs;
    }
}
