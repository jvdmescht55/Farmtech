<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $products = collect();

        if ($query !== '') {
            $products = Product::storefrontVisible()
                ->with(['thumbnail', 'complianceAudit'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhereHas('specs', function ($specQuery) use ($query) {
                            $specQuery->where('spec_value', 'like', "%{$query}%")
                                ->orWhere('spec_key', 'like', "%{$query}%");
                        });
                })
                ->latest()
                ->paginate(12)
                ->withQueryString();
        }

        return view('storefront.search', [
            'query' => $query,
            'products' => $products,
        ]);
    }

    /** JSON, for the header search bar's live-typeahead preview — same match logic as the full results page, just capped and slim. */
    public function suggest(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $products = Product::storefrontVisible()
            ->with('thumbnail')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(6)
            ->get()
            ->map(fn (Product $product) => [
                'title' => $product->title,
                'category' => $product->category_label,
                'price' => number_format((float) $product->retail_price_zar, 2),
                'url' => route('products.show', $product),
                'image' => $product->thumbnail?->url,
            ]);

        return response()->json(['results' => $products]);
    }
}
