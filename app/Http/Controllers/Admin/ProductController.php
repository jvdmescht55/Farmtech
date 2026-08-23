<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

    public function show(Product $product)
    {
        $product->load('images');
        return view('admin.products.show', compact('product'));
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
        ]);

        $product->fill($request->only([
            'title', 'short_description', 'requirements_notes',
            'compatibility_notes', 'retail_price_zar', 'stock_availability_type'
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
            'published_at' => now()
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
}
