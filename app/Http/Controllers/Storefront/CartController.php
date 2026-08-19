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
        $this->cart->add($product->id, $quantity);

        return back()->with('status', "Added \"{$product->title}\" to your cart.");
    }

    public function update(Request $request, Product $product)
    {
        $this->cart->update($product->id, (int) $request->input('quantity', 1));

        return back();
    }

    public function remove(Product $product)
    {
        $this->cart->remove($product->id);

        return back();
    }
}
