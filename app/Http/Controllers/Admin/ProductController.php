<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\LandedCostCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['complianceAudit', 'thumbnail'])->latest();

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $status = $request->query('status', 'pending_review');
        if ($status) {
            $query->status($status);
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
            'filters' => $request->only(['status', 'category', 'verdict', 'search']),
        ]);
    }

    /** Review panel: real financial breakdown from LandedCostCalculator, not ad-hoc numbers in the view. */
    public function show(Product $product)
    {
        $product->load(['images', 'variants', 'reviews']);

        $calculator = new LandedCostCalculator(
            freightUsdPerKg: (float) Setting::get('air_freight_usd_per_kg', 16),
            domesticDeliveryZar: (float) Setting::get('clearing_agent_fee_zar', 250),
            vatRate: (float) Setting::get('vat_rate', 0.15),
        );

        $weightKg = (float) ($product->gross_weight_kg ?: $product->est_weight_kg ?: 0.5);
        $exchangeRate = (float) ($product->exchange_rate ?: 18.50);
        $dutyRate = (float) ($product->customs_duty_rate ?: 0.10);
        $marginPct = (float) ($product->profit_margin_pct ?: Setting::get('target_margin_pct', 35));

        $breakdown = $calculator->calculate(
            (float) $product->original_price_usd,
            $weightKg,
            $exchangeRate,
            $dutyRate,
            $marginPct,
        );

        return view('admin.products.show', compact('product', 'breakdown'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'requirements_notes' => 'nullable|string',
            'compatibility_notes' => 'nullable|string',
            'retail_price_zar' => 'nullable|numeric',
            'stock_availability_type' => 'nullable|string',
            'lead_time_days' => 'nullable|string|max:100',
        ]);

        $product->fill($request->only([
            'title', 'short_description', 'requirements_notes',
            'compatibility_notes', 'retail_price_zar', 'stock_availability_type', 'lead_time_days',
        ]));

        if ($request->filled('title')) {
            $product->slug = Str::slug($request->title) . '-' . $product->id;
        }

        if ($request->has('included_items')) {
            $raw = $request->input('included_items');
            $product->included_items = is_array($raw) ? $raw : array_filter(array_map('trim', explode("\n", (string)$raw)));
        }

        $product->radio_frequency_confirmed = $request->boolean('radio_frequency_confirmed');
        $product->datasheet_uploaded = $request->boolean('datasheet_uploaded');

        if ($request->input('action') === 'publish') {
            $product->status = 'approved';
            $product->is_active = true;
            $product->published_at = now();
            $product->approved_at = now();
            $product->published_via = 'manual';
        }

        $product->save();

        $message = $request->input('action') === 'publish'
            ? 'Product successfully verified and published to store!'
            : 'Draft changes saved successfully.';

        return redirect()->route('admin.products.show', $product)->with('success', $message);
    }

    public function approve(Product $product)
    {
        $product->update([
            'status' => 'approved',
            'is_active' => true,
            'approved_at' => now(),
            'published_at' => now(),
            'published_via' => 'manual',
        ]);
        return back()->with('success', 'Product approved and published.');
    }

    public function reject(Product $product)
    {
        $product->update([
            'status' => 'rejected',
            'is_active' => false
        ]);
        return back()->with('success', 'Product rejected.');
    }

    /** Everything actually live on the storefront right now, grouped by industry/category — same grouping the public catalogue uses. */
    public function live()
    {
        $products = Product::storefrontVisible()
            ->with('thumbnail')
            ->orderBy('title')
            ->get()
            ->groupBy(fn (Product $product) => $product->category->value);

        return view('admin.products.live', [
            'industries' => \App\Enums\Industry::cases(),
            'grouped' => $products,
        ]);
    }

    /** Pulls a listing off the storefront without deleting it. */
    public function archive(Product $product)
    {
        $product->update([
            'status' => 'archived',
            'is_active' => false,
        ]);

        return back()->with('success', 'Listing removed from the store and archived.');
    }

    /** Sends an archived/rejected listing back to the review queue — never auto-publishes. */
    public function relist(Product $product)
    {
        $product->update([
            'status' => 'pending_review',
            'is_active' => false,
        ]);

        return back()->with('success', 'Listing sent back to the review queue.');
    }
}
