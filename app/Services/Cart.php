<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

/** Thin wrapper around a session-stored cart: {product_id => quantity}. */
class Cart
{
    private const SESSION_KEY = 'cart';

    public function items(): array
    {
        $raw = Session::get(self::SESSION_KEY, []);

        if (empty($raw)) {
            return [];
        }

        $products = Product::storefrontVisible()->whereIn('id', array_keys($raw))->with('thumbnail')->get()->keyBy('id');

        $items = [];
        foreach ($raw as $productId => $quantity) {
            if ($product = $products->get($productId)) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => round((float) $product->retail_price_zar * $quantity, 2),
                ];
            }
        }

        return $items;
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $cart[$productId] = ($cart[$productId] ?? 0) + max(1, $quantity);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = Session::get(self::SESSION_KEY, []);

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function subtotal(): float
    {
        return round(array_sum(array_column($this->items(), 'line_total')), 2);
    }

    public function count(): int
    {
        return array_sum(Session::get(self::SESSION_KEY, []));
    }
}
