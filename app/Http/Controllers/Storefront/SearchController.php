<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Applications;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const SORTS = ['newest', 'price_asc', 'price_desc', 'popularity'];

    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';

        $products = collect();

        if ($query !== '') {
            $productsQuery = Product::storefrontVisible()
                ->with(['thumbnail', 'complianceAudit', 'specs'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        // Description prose often carries the real-world application
                        // language ("for the crush, race, or loading ramp") that a
                        // buyer searching by task rather than product name would type.
                        ->orWhere('description_html', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhereHas('specs', function ($specQuery) use ($query) {
                            $specQuery->where('spec_value', 'like', "%{$query}%")
                                ->orWhere('spec_key', 'like', "%{$query}%");
                        });
                });

            match ($sort) {
                'price_asc' => $productsQuery->orderBy('retail_price_zar'),
                'price_desc' => $productsQuery->orderByDesc('retail_price_zar'),
                'popularity' => $productsQuery->withSum('orderItems as units_sold', 'quantity')->orderByDesc('units_sold')->orderByDesc('created_at'),
                default => $productsQuery->latest(),
            };

            $products = $productsQuery->paginate(12)->withQueryString();
        }

        return view('storefront.search', [
            'query' => $query,
            'products' => $products,
            'sort' => $sort,
        ]);
    }

    /**
     * JSON, for the header search bar's live-typeahead preview — grouped into
     * Products / Categories / Applications, since a technical-equipment buyer
     * searching "cattle scale" benefits from landing on the right category or
     * application page just as much as an exact product match.
     */
    public function suggest(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'applications' => []]);
        }

        $products = Product::storefrontVisible()
            ->with('thumbnail')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhereHas('specs', function ($specQuery) use ($query) {
                        $specQuery->where('spec_value', 'like', "%{$query}%")
                            ->orWhere('spec_key', 'like', "%{$query}%");
                    });
            })
            ->limit(6)
            ->get()
            ->map(fn (Product $product) => [
                'title' => $product->title,
                'category' => $product->category_label,
                'price' => number_format((float) $product->retail_price_zar, 0, '', ' '),
                'url' => route('products.show', $product),
                'image' => $product->thumbnail?->url,
            ]);

        $needle = strtolower($query);
        $categories = collect(ProductCategory::cases())
            ->filter(fn (ProductCategory $c) => str_contains(strtolower($c->label()), $needle) || str_contains(strtolower($c->shortLabel()), $needle))
            ->take(5)
            ->map(fn (ProductCategory $c) => ['label' => $c->label(), 'url' => route('category.show', $c)])
            ->values();

        $applications = collect(Applications::matching($query))
            ->take(4)
            ->map(fn (array $app) => ['label' => $app['label'], 'url' => Applications::url($app)])
            ->values();

        return response()->json(['products' => $products, 'categories' => $categories, 'applications' => $applications]);
    }
}
