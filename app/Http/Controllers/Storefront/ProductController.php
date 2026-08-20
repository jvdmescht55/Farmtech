<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Industry;
use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const SORTS = ['newest', 'price_asc', 'price_desc', 'popularity'];

    public function category(ProductCategory $category, Request $request)
    {
        [$products, $sort, $inStock] = $this->filteredAndSorted(
            Product::storefrontVisible()->category($category->value),
            $request
        );

        return view('storefront.products.category', [
            'category' => $category,
            'products' => $products,
            'sort' => $sort,
            'inStock' => $inStock,
        ]);
    }

    public function industry(Industry $industry, Request $request)
    {
        [$products, $sort, $inStock] = $this->filteredAndSorted(
            Product::storefrontVisible()->industry($industry),
            $request
        );

        return view('storefront.products.industry', [
            'industry' => $industry,
            'products' => $products,
            'sort' => $sort,
            'inStock' => $inStock,
        ]);
    }

    /** @return array{0: \Illuminate\Contracts\Pagination\LengthAwarePaginator, 1: string, 2: bool} */
    private function filteredAndSorted(Builder $query, Request $request): array
    {
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';
        $inStock = $request->boolean('in_stock');

        $query->with(['thumbnail', 'complianceAudit']);

        if ($inStock) {
            $query->where('stock_status', 'in_stock');
        }

        match ($sort) {
            'price_asc' => $query->orderBy('retail_price_zar'),
            'price_desc' => $query->orderByDesc('retail_price_zar'),
            // Same real units-sold ranking as the Top-5 Trending strip — never a fabricated "popularity" score.
            'popularity' => $query->withSum('orderItems as units_sold', 'quantity')->orderByDesc('units_sold')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return [$query->paginate(12)->withQueryString(), $sort, $inStock];
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
