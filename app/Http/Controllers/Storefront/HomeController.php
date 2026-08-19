<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = ['scales', 'ultrasound', 'rfid', 'accessories'];

        $base = Product::storefrontVisible()->with(['thumbnail', 'complianceAudit'])->latest();

        $heroProducts = (clone $base)->limit(5)->get();
        $featured = (clone $base)->limit(8)->get();

        return view('storefront.home', [
            'categories' => $categories,
            'heroProducts' => $heroProducts,
            'featured' => $featured,
        ]);
    }
}
