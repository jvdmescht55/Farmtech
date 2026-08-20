<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Industry;
use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const SORTS = ['newest', 'price_asc', 'price_desc', 'popularity'];

    public function category(ProductCategory $category, Request $request)
    {
        [$products, $sort, $inStock, $facets, $selectedSpecs] = $this->filteredAndSorted(
            Product::storefrontVisible()->category($category->value),
            $request
        );

        return view('storefront.products.category', [
            'category' => $category,
            'products' => $products,
            'sort' => $sort,
            'inStock' => $inStock,
            'facets' => $facets,
            'selectedSpecs' => $selectedSpecs,
        ]);
    }

    public function industry(Industry $industry, Request $request)
    {
        [$products, $sort, $inStock, $facets, $selectedSpecs] = $this->filteredAndSorted(
            Product::storefrontVisible()->industry($industry),
            $request
        );

        return view('storefront.products.industry', [
            'industry' => $industry,
            'products' => $products,
            'sort' => $sort,
            'inStock' => $inStock,
            'facets' => $facets,
            'selectedSpecs' => $selectedSpecs,
        ]);
    }

    /**
     * Spec-based filter options are built from what the pipeline actually
     * recorded for products in this category/industry — never a hardcoded
     * per-category schema (capacity/resolution/etc.), since that would
     * either invent options that don't correspond to real inventory or need
     * a taxonomy this codebase doesn't have. Facets are computed from the
     * category/industry + in-stock scope only (not from the currently
     * selected spec filters), so picking one filter doesn't make the others
     * disappear.
     *
     * @return array{0: \Illuminate\Contracts\Pagination\LengthAwarePaginator, 1: string, 2: bool, 3: \Illuminate\Support\Collection, 4: array}
     */
    private function filteredAndSorted(Builder $baseQuery, Request $request): array
    {
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';
        $inStock = $request->boolean('in_stock');
        $selectedSpecs = array_filter((array) $request->input('spec', []));

        $facetScope = clone $baseQuery;
        if ($inStock) {
            $facetScope->where('stock_status', 'in_stock');
        }
        $facetProductIds = $facetScope->pluck('id');

        $facets = ProductSpec::whereIn('product_id', $facetProductIds)
            ->select('spec_key', 'spec_value')
            ->distinct()
            ->get()
            ->groupBy('spec_key')
            ->map(fn ($rows) => $rows->pluck('spec_value')->unique()->sort()->values())
            ->sortKeys();

        $query = clone $baseQuery;
        $query->with(['thumbnail', 'complianceAudit', 'specs']);

        if ($inStock) {
            $query->where('stock_status', 'in_stock');
        }

        foreach ($selectedSpecs as $specKey => $specValues) {
            $specValues = array_values(array_filter((array) $specValues));
            if (empty($specValues)) {
                continue;
            }

            $query->whereHas('specs', function ($specQuery) use ($specKey, $specValues) {
                $specQuery->where('spec_key', $specKey)->whereIn('spec_value', $specValues);
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('retail_price_zar'),
            'price_desc' => $query->orderByDesc('retail_price_zar'),
            // Same real units-sold ranking as the Top-5 Trending strip — never a fabricated "popularity" score.
            'popularity' => $query->withSum('orderItems as units_sold', 'quantity')->orderByDesc('units_sold')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return [$query->paginate(12)->withQueryString(), $sort, $inStock, $facets, $selectedSpecs];
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved' && $product->is_active, 404);

        $product->load(['images', 'specs', 'complianceAudit']);

        $related = Product::storefrontVisible()
            ->category($product->category->value)
            ->where('id', '!=', $product->id)
            ->with(['thumbnail', 'complianceAudit', 'specs'])
            ->limit(4)
            ->get();

        return view('storefront.products.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
