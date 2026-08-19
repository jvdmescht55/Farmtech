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

        return view('storefront.home', [
            'categories' => ProductCategory::cases(),
            'heroProducts' => $heroProducts,
            'featured' => $featured,
        ]);
    }
}
