<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = ['scales', 'ultrasound', 'rfid', 'accessories'];

        $featured = Product::storefrontVisible()
            ->with('thumbnail')
            ->latest()
            ->limit(8)
            ->get();

        return view('storefront.home', [
            'categories' => $categories,
            'featured' => $featured,
        ]);
    }
}
