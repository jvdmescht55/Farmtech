<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Session;

/**
 * Thin wrapper around a session-stored cart. Each line is keyed
 * "{productId}|{variantId}" (variantId is '' for a product with no
 * variant selected, so a plain product's key is just "123|") mapped to a
 * plain int quantity. A pre-variant session cart's bare-product-id keys
 * ("123", no separator) still parse correctly here — explode() on a key
 * with no "|" yields the id with an empty variantId, the same as the
 * no-variant format — so an in-progress cart survives this change without
 * needing a migration step of its own.
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    public function items(): array
    {
        $raw = Session::get(self::SESSION_KEY, []);

        if (empty($raw)) {
            return [];
        }

        $lines = [];
        foreach ($raw as $lineKey => $quantity) {
            if (!is_numeric($quantity)) {
                continue; // malformed entry (e.g. a stale pre-migration session) — skip, not fatal
            }

            [$productId, $variantId] = self::parseLineKey((string) $lineKey);

            if ($productId === null) {
                continue;
            }

            $lines[] = ['line_key' => (string) $lineKey, 'product_id' => $productId, 'variant_id' => $variantId, 'quantity' => (int) $quantity];
        }

        if (empty($lines)) {
            return [];
        }

        $products = Product::storefrontVisible()
            ->whereIn('id', array_unique(array_column($lines, 'product_id')))
            ->with('thumbnail')
            ->get()
            ->keyBy('id');

        $variantIds = array_values(array_filter(array_column($lines, 'variant_id')));
        $variants = $variantIds ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();

        $items = [];
        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);

            if (!$product) {
                continue;
            }

            $variant = $line['variant_id'] ? $variants->get($line['variant_id']) : null;

            // A variant id that no longer belongs to this product (deleted,
            // or a rescrape replaced the variant set) is treated as "no
            // variant selected" rather than silently pricing the line
            // against a stale/unrelated variant.
            if ($variant && $variant->product_id !== $product->id) {
                $variant = null;
            }

            $unitPrice = $variant ? (float) $variant->retail_price_zar : (float) $product->retail_price_zar;

            $items[] = [
                'line_key' => $line['line_key'],
                'product' => $product,
                'variant' => $variant,
                'variant_id' => $variant?->id,
                'quantity' => $line['quantity'],
                'unit_price_zar' => round($unitPrice, 2),
                'line_total' => round($unitPrice * $line['quantity'], 2),
            ];
        }

        return $items;
    }

    public function add(int $productId, int $quantity = 1, ?int $variantId = null): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $lineKey = self::lineKey($productId, $variantId);
        $cart[$lineKey] = ($cart[$lineKey] ?? 0) + max(1, $quantity);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function update(int $productId, int $quantity, ?int $variantId = null): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $lineKey = self::lineKey($productId, $variantId);

        if ($quantity <= 0) {
            unset($cart[$lineKey]);
        } else {
            $cart[$lineKey] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[self::lineKey($productId, $variantId)]);
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

    /** JSON-friendly snapshot for the cart drawer's Alpine store — same items()/subtotal() data, flattened for the front end. */
    public function summary(): array
    {
        $items = array_map(function (array $item) {
            return [
                'line_key' => $item['line_key'],
                'product_id' => $item['product']->id,
                'variant_id' => $item['variant_id'],
                'title' => $item['product']->title,
                'variant_label' => $item['variant']?->option_name,
                'url' => route('products.show', $item['product']),
                'image' => $item['product']->thumbnail?->url,
                'quantity' => $item['quantity'],
                'unit_price_zar' => $item['unit_price_zar'],
                'line_total' => $item['line_total'],
            ];
        }, $this->items());

        return [
            'items' => $items,
            'count' => (int) array_sum(array_column($items, 'quantity')),
            'subtotal' => round(array_sum(array_column($items, 'line_total')), 2),
        ];
    }

    public function count(): int
    {
        return (int) array_sum(array_filter(Session::get(self::SESSION_KEY, []), 'is_numeric'));
    }

    private static function lineKey(int $productId, ?int $variantId): string
    {
        return "{$productId}|" . ($variantId ?? '');
    }

    /** @return array{0: int|null, 1: int|null} */
    private static function parseLineKey(string $lineKey): array
    {
        $parts = explode('|', $lineKey, 2);
        $productId = filter_var($parts[0] ?? '', FILTER_VALIDATE_INT);

        if ($productId === false || $productId <= 0) {
            return [null, null];
        }

        $variantId = isset($parts[1]) ? filter_var($parts[1], FILTER_VALIDATE_INT) : false;

        return [$productId, $variantId !== false ? $variantId : null];
    }
}
