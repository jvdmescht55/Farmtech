<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Industry;
use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // Real curation score (margin, compactness, media richness, reviews,
        // verified-supplier trust, units sold, click-throughs — see
        // Product::computeFeaturedScore()), recomputed on a schedule by
        // `products:rank-featured` — never a hand-picked or "latest" list.
        $featured = Product::query()->featured()->with(['thumbnail', 'complianceAudit', 'specs'])->limit(12)->get();
        $trending = Product::query()->trending()->with(['thumbnail', 'complianceAudit', 'specs'])->limit(5)->get();

        // Wider real-trending slice for the marquee strip — same ranking as
        // above, not a separately-curated list.
        $marqueeProducts = Product::query()->trending()->with(['thumbnail', 'complianceAudit', 'specs'])->limit(10)->get();

        // Real cost breakdown for the landed-cost transparency widget — never
        // a fabricated "vs local retail" comparison, since we have no real
        // competitor pricing data source. Falls back to trending when
        // featured is still empty (before the first products:rank-featured
        // run), so this section doesn't just disappear on a fresh catalog.
        $costSample = $featured->concat($trending)->first(fn (Product $p) => $p->landed_cost_zar && $p->retail_price_zar);

        return view('storefront.home', [
            'industries' => Industry::activeCases(),
            'featured' => $featured,
            'trending' => $trending,
            'marqueeProducts' => $marqueeProducts,
            'costSample' => $costSample,
        ]);
    }
}
