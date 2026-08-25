<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Industry;
use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $base = Product::storefrontVisible()->with(['thumbnail', 'complianceAudit', 'specs'])->latest();

        $featured = (clone $base)->limit(8)->get();
        $trending = Product::query()->trending()->with(['thumbnail', 'complianceAudit', 'specs'])->limit(5)->get();

        // Same real trending ranking as above, just a wider slice for the
        // marquee strip — never a separately-curated/fabricated "featured" list.
        $marqueeProducts = Product::query()->trending()->with(['thumbnail', 'complianceAudit', 'specs'])->limit(10)->get();

        // Real cost breakdown for the landed-cost transparency widget — never
        // a fabricated "vs local retail" comparison, since we have no real
        // competitor pricing data source.
        $costSample = $featured->first(fn (Product $p) => $p->landed_cost_zar && $p->retail_price_zar);

        return view('storefront.home', [
            'industries' => Industry::activeCases(),
            'featured' => $featured,
            'trending' => $trending,
            'marqueeProducts' => $marqueeProducts,
            'costSample' => $costSample,
        ]);
    }
}
