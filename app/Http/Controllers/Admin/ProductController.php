<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedSupplier;
use App\Models\Product;
use App\Models\Setting;
use App\Services\LandedCostCalculator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Staging queue: filterable table of items awaiting review. */
    public function index(Request $request)
    {
        $query = Product::query()->with(['complianceAudit', 'thumbnail'])->latest();

        if ($status = $request->query('status')) {
            $query->status($status);
        } else {
            $query->status('pending_review');
        }

        if ($category = $request->query('category')) {
            $query->category($category);
        }

        if ($verdict = $request->query('verdict')) {
            $query->whereHas('complianceAudit', fn ($q) => $q->where('audit_verdict', $verdict));
        }

        $products = $query->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $request->only(['status', 'category', 'verdict']),
        ]);
    }

    /** Product review panel: specs/compliance sidebar + financial breakdown. */
    public function show(Product $product)
    {
        $product->load(['specs', 'images', 'complianceAudit']);

        $calculator = $this->calculator();
        $usdZarRate = \App\Models\ExchangeRate::latestRate('USDZAR') ?? 18.50;

        return view('admin.products.show', [
            'product' => $product,
            'usdZarRate' => $usdZarRate,
            'breakdown' => $calculator->calculate(
                (float) $product->original_price_usd,
                (float) $product->est_weight_kg,
                $usdZarRate,
                (float) $product->customs_duty_rate,
                (float) ($product->profit_margin_pct ?? Setting::get('target_margin_pct', 35)),
            ),
        ]);
    }

    /** AJAX: recompute the financial breakdown as the admin drags the margin slider. */
    public function recalculate(Request $request, Product $product)
    {
        $validated = $request->validate([
            'margin_pct' => ['required', 'numeric', 'min:0', 'max:95'],
        ]);

        $calculator = $this->calculator();
        $usdZarRate = \App\Models\ExchangeRate::latestRate('USDZAR') ?? 18.50;

        $breakdown = $calculator->calculate(
            (float) $product->original_price_usd,
            (float) $product->est_weight_kg,
            $usdZarRate,
            (float) $product->customs_duty_rate,
            (float) $validated['margin_pct'],
        );

        return response()->json($breakdown);
    }

    /** Quick edit: inline editing of title, specs, retail price. */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'retail_price_zar' => ['required', 'numeric', 'min:0'],
            'profit_margin_pct' => ['required', 'numeric', 'min:0', 'max:95'],
            'stock_status' => ['required', 'in:in_stock,pre_order'],
            'lead_time_days' => ['required', 'string', 'max:100'],
        ]);

        $product->update($validated);

        return back()->with('status', 'Product updated.');
    }

    public function approve(Product $product)
    {
        $product->update(['status' => 'approved', 'is_active' => true]);

        return redirect()->route('admin.products.index')->with('status', "\"{$product->title}\" approved and published.");
    }

    /** Reject & optionally blacklist the supplier. */
    public function reject(Request $request, Product $product)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'blacklist_supplier' => ['sometimes', 'boolean'],
        ]);

        $product->update(['status' => 'rejected', 'is_active' => false]);

        $supplierName = $product->complianceAudit?->supplier_name;

        if ($supplierName && $request->boolean('blacklist_supplier')) {
            BlacklistedSupplier::firstOrCreate(
                ['supplier_name' => $supplierName],
                ['reason' => $validated['reason'] ?? 'Rejected during admin review', 'blacklisted_by' => auth()->id()]
            );
        }

        return redirect()->route('admin.products.index')->with('status', "\"{$product->title}\" rejected.");
    }

    private function calculator(): LandedCostCalculator
    {
        return new LandedCostCalculator(
            freightUsdPerKg: (float) Setting::get('air_freight_usd_per_kg', 9.5),
            clearingFeeZar: (float) Setting::get('clearing_agent_fee_zar', 450),
            vatRate: (float) Setting::get('vat_rate', 0.15),
        );
    }
}
