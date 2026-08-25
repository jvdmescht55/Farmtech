<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index()
    {
        return view('storefront.cart.index', [
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->status === 'approved' && $product->is_active, 404);

        $quantity = (int) $request->input('quantity', 1);
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;

        // A product with variants requires a real, existing one of its own
        // — an empty/garbage variant_id would otherwise add a line priced
        // off the product's own base retail_price_zar, which for a
        // variant-priced listing (e.g. every range/package option priced
        // independently) was never meant to be sold on its own.
        if ($product->hasVariants() && (!$variantId || !$product->variants->contains('id', $variantId))) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Please select a valid option before adding this item to your cart.'], 422);
            }

            return back()->withErrors(['variant' => 'Please select a valid option before adding this item to your cart.']);
        }

        $this->cart->add($product->id, $quantity, $product->hasVariants() ? $variantId : null);

        if ($request->wantsJson()) {
            return response()->json($this->cart->summary());
        }

        return back()->with('status', "Added \"{$product->title}\" to your cart.");
    }

    public function update(Request $request, Product $product)
    {
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $this->cart->update($product->id, (int) $request->input('quantity', 1), $variantId);

        if ($request->wantsJson()) {
            return response()->json($this->cart->summary());
        }

        return back();
    }

    public function remove(Request $request, Product $product)
    {
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $this->cart->remove($product->id, $variantId);

        if ($request->wantsJson()) {
            return response()->json($this->cart->summary());
        }

        return back();
    }

    /** JSON snapshot used to bootstrap the cart drawer's Alpine store on page load. */
    public function summary()
    {
        return response()->json($this->cart->summary());
    }
}
