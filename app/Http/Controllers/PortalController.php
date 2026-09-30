<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Models\Product;

class PortalController extends Controller
{
    public function index()
    {
        $rfidCount = Product::query()
            ->where('status', 'approved')->where('is_active', true)
            ->whereIn('category', [ProductCategory::Rfid->value, ProductCategory::IndustrialRfid->value])
            ->count();

        return view('public.portal', ['rfidCount' => $rfidCount, 'productCount' => Product::where('status', 'approved')->where('is_active', true)->count()]);
    }
}
