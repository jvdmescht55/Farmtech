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
        $variantKey = (string) $request->input('variant_key', '');

        // A product with variants requires a real, existing combination —
        // an empty/garbage variant_key would otherwise add an unpriced
        // "base" line for a product whose base price was never meant to be
        // sold on its own (e.g. every SKU has a color/wattage delta).
        if ($product->hasVariants() && !$product->findVariantByKey($variantKey)) {
            return back()->withErrors(['variant' => 'Please select a valid option before adding this item to your cart.']);
        }

        $this->cart->add($product->id, $quantity, $product->hasVariants() ? $variantKey : '');

        return back()->with('status', "Added \"{$product->title}\" to your cart.");
    }

    public function update(Request $request, Product $product)
    {
        $variantKey = (string) $request->input('variant_key', '');
        $this->cart->update($product->id, (int) $request->input('quantity', 1), $variantKey);

        return back();
    }

    public function remove(Request $request, Product $product)
    {
        $variantKey = (string) $request->input('variant_key', '');
        $this->cart->remove($product->id, $variantKey);

        return back();
    }
}
