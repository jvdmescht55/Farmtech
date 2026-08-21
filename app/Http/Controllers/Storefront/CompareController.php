<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    /**
     * JSON for the compare drawer — up to 3 real products with their real
     * spec rows, aligned on spec_key so the drawer can show matching
     * attributes side by side. A spec key one product doesn't have is left
     * blank for that column, never filled with an invented value.
     */
    public function data(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        $products = Product::storefrontVisible()
            ->with(['thumbnail', 'specs'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => $ids->search($product->id))
            ->values();

        $specKeys = $products
            ->flatMap(fn (Product $product) => $product->specs->pluck('spec_key'))
            ->unique()
            ->sortByDesc(function (string $key) use ($products) {
                return $products->filter(fn (Product $product) => $product->specs->firstWhere('spec_key', $key))->count();
            })
            ->values();

        return response()->json([
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category_label,
                'url' => route('products.show', $product),
                'image' => $product->thumbnail?->url,
                'price' => number_format((float) $product->retail_price_zar, 0, '', ' '),
                'specs' => $product->specs->pluck('spec_value', 'spec_key'),
            ])->values(),
            'specKeys' => $specKeys,
        ]);
    }
}
