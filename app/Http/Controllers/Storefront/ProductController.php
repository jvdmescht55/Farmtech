<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function category(string $category)
    {
        abort_unless(in_array($category, ['scales', 'ultrasound', 'rfid', 'accessories'], true), 404);

        $products = Product::storefrontVisible()
            ->category($category)
            ->with('thumbnail')
            ->paginate(12);

        return view('storefront.products.category', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved' && $product->is_active, 404);

        $product->load(['images', 'specs']);

        $related = Product::storefrontVisible()
            ->category($product->category)
            ->where('id', '!=', $product->id)
            ->with('thumbnail')
            ->limit(4)
            ->get();

        return view('storefront.products.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
