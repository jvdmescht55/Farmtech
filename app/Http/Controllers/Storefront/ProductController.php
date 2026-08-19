<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function category(ProductCategory $category)
    {
        $products = Product::storefrontVisible()
            ->category($category->value)
            ->with(['thumbnail', 'complianceAudit'])
            ->paginate(12);

        return view('storefront.products.category', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved' && $product->is_active, 404);

        $product->load(['images', 'specs', 'complianceAudit']);

        $related = Product::storefrontVisible()
            ->category($product->category->value)
            ->where('id', '!=', $product->id)
            ->with(['thumbnail', 'complianceAudit'])
            ->limit(4)
            ->get();

        return view('storefront.products.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
