<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const SORTS = ['newest', 'price_asc', 'price_desc', 'popularity'];

    public function category(ProductCategory $category, Request $request)
    {
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';

        $query = Product::storefrontVisible()
            ->category($category->value)
            ->with(['thumbnail', 'complianceAudit']);

        if ($request->boolean('in_stock')) {
            $query->where('stock_status', 'in_stock');
        }

        match ($sort) {
            'price_asc' => $query->orderBy('retail_price_zar'),
            'price_desc' => $query->orderByDesc('retail_price_zar'),
            // Same real units-sold ranking as the Top-5 Trending strip — never a fabricated "popularity" score.
            'popularity' => $query->withSum('orderItems as units_sold', 'quantity')->orderByDesc('units_sold')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        return view('storefront.products.category', [
            'category' => $category,
            'products' => $products,
            'sort' => $sort,
            'inStock' => $request->boolean('in_stock'),
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
