<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $base = Product::storefrontVisible()->with(['thumbnail', 'complianceAudit'])->latest();

        $heroProducts = (clone $base)->limit(5)->get();
        $featured = (clone $base)->limit(8)->get();
        $trending = Product::query()->trending()->with('thumbnail')->limit(5)->get();

        // Real cost breakdown for the landed-cost transparency widget — never
        // a fabricated "vs local retail" comparison, since we have no real
        // competitor pricing data source.
        $costSample = $featured->first(fn (Product $p) => $p->landed_cost_zar && $p->retail_price_zar);

        return view('storefront.home', [
            'categories' => ProductCategory::cases(),
            'heroProducts' => $heroProducts,
            'featured' => $featured,
            'trending' => $trending,
            'costSample' => $costSample,
        ]);
    }
}
