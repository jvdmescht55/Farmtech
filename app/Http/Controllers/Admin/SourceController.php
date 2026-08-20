<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Services\SourcingPipelineRunner;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Closes the "login and hit edit or post" loop: an admin fills in what they
 * saw on a supplier listing, this shells out to the real Node pipeline
 * (worker/src/pipeline.js — same code path as the CLI, same Gemini vetting,
 * same landed-cost math) with a cwd of worker/ so it picks up worker/.env
 * exactly like a manual run would, and lands the admin straight on the
 * review page for whatever got inserted (staged or rejected).
 */
class SourceController extends Controller
{
    public function create()
    {
        return view('admin.source.create', [
            'categories' => ProductCategory::cases(),
        ]);
    }

    public function store(Request $request, SourcingPipelineRunner $runner)
    {
        $validated = $request->validate([
            'raw_title' => ['required', 'string', 'max:255'],
            'category_hint' => ['required', Rule::enum(ProductCategory::class)],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_years' => ['nullable', 'integer', 'min:0'],
            'is_verified_supplier' => ['sometimes', 'boolean'],
            'has_trade_assurance' => ['sometimes', 'boolean'],
            'supplier_price_usd' => ['required', 'numeric', 'min:0'],
            'weight_kg' => ['required', 'numeric', 'min:0'],
            'duty_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'stock_status' => ['required', 'in:in_stock,pre_order'],
            'lead_time_days' => ['nullable', 'string', 'max:100'],
            'category_price_hint' => ['nullable', 'numeric', 'min:0'],
            'raw_specs_text' => ['required', 'string'],
            'image_urls' => ['nullable', 'string'],
        ]);

        $sku = 'FT-'.Str::upper(Str::random(4)).'-'.now()->format('mdHi');

        $listing = [
            'sku' => $sku,
            'raw_title' => $validated['raw_title'],
            'category_hint' => $validated['category_hint'],
            'supplier_name' => $validated['supplier_name'],
            'supplier_years' => $validated['supplier_years'] ?? null,
            'is_verified_supplier' => $request->boolean('is_verified_supplier'),
            'has_trade_assurance' => $request->boolean('has_trade_assurance'),
            'supplier_price_usd' => (float) $validated['supplier_price_usd'],
            'weight_kg' => (float) $validated['weight_kg'],
            'duty_rate' => (float) $validated['duty_rate'],
            'stock_status' => $validated['stock_status'],
            'lead_time_days' => $validated['lead_time_days'] ?: '7-12 business days',
            'category_price_hint' => isset($validated['category_price_hint']) ? (float) $validated['category_price_hint'] : null,
            'raw_specs_text' => $validated['raw_specs_text'],
            'image_urls' => array_values(array_filter(array_map('trim', explode("\n", $validated['image_urls'] ?? '')))),
        ];

        $result = $runner->run([$listing]);
        $item = $result->items[0] ?? null;

        if ($item && isset($item['productId'])) {
            $verdict = $item['status'] === 'rejected' ? 'rejected — see the review page for why' : 'staged for review';

            return redirect()
                ->route('admin.products.show', $item['productId'])
                ->with('status', "Sourced \"{$validated['raw_title']}\" — {$verdict}.");
        }

        $errorMessage = $item['error'] ?? 'The sourcing pipeline did not report a successful insert. Raw output below.';

        return back()->withInput()->withErrors([
            'raw_title' => $errorMessage,
        ])->with('pipeline_output', $result->rawOutput);
    }
}
