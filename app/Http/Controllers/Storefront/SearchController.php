<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $products = collect();

        if ($query !== '') {
            $products = Product::storefrontVisible()
                ->with(['thumbnail', 'complianceAudit'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhereHas('specs', function ($specQuery) use ($query) {
                            $specQuery->where('spec_value', 'like', "%{$query}%")
                                ->orWhere('spec_key', 'like', "%{$query}%");
                        });
                })
                ->latest()
                ->paginate(12)
                ->withQueryString();
        }

        return view('storefront.search', [
            'query' => $query,
            'products' => $products,
        ]);
    }
}
