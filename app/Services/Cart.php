<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

/**
 * Thin wrapper around a session-stored cart. Each line is keyed
 * "{productId}|{variantKey}" (variantKey is '' for a product with no
 * variants, so a plain product's key is just "123|") mapped to a plain int
 * quantity. A pre-variant session cart's bare-product-id keys ("123", no
 * separator) still parse correctly here — explode() on a key with no "|"
 * yields the id with an empty variantKey, the same as the new no-variant
 * format — so an in-progress cart survives this change without needing a
 * migration step of its own.
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

            [$productId, $variantKey] = self::parseLineKey((string) $lineKey);

            if ($productId === null) {
                continue;
            }

            $lines[] = ['line_key' => (string) $lineKey, 'product_id' => $productId, 'variant_key' => $variantKey, 'quantity' => (int) $quantity];
        }

        if (empty($lines)) {
            return [];
        }

        $products = Product::storefrontVisible()
            ->whereIn('id', array_unique(array_column($lines, 'product_id')))
            ->with('thumbnail')
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);

            if (!$product) {
                continue;
            }

            $variant = $line['variant_key'] !== '' ? $product->findVariantByKey($line['variant_key']) : null;
            $unitPrice = round((float) $product->retail_price_zar + (float) ($variant['price_delta_zar'] ?? 0), 2);

            $items[] = [
                'line_key' => $line['line_key'],
                'product' => $product,
                'variant' => $variant,
                'variant_key' => $line['variant_key'],
                'quantity' => $line['quantity'],
                'unit_price_zar' => $unitPrice,
                'line_total' => round($unitPrice * $line['quantity'], 2),
            ];
        }

        return $items;
    }

    public function add(int $productId, int $quantity = 1, string $variantKey = ''): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $lineKey = self::lineKey($productId, $variantKey);
        $cart[$lineKey] = ($cart[$lineKey] ?? 0) + max(1, $quantity);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function update(int $productId, int $quantity, string $variantKey = ''): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $lineKey = self::lineKey($productId, $variantKey);

        if ($quantity <= 0) {
            unset($cart[$lineKey]);
        } else {
            $cart[$lineKey] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId, string $variantKey = ''): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[self::lineKey($productId, $variantKey)]);
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
        return (int) array_sum(array_filter(Session::get(self::SESSION_KEY, []), 'is_numeric'));
    }

    private static function lineKey(int $productId, string $variantKey): string
    {
        return "{$productId}|{$variantKey}";
    }

    /** @return array{0: int|null, 1: string} */
    private static function parseLineKey(string $lineKey): array
    {
        $parts = explode('|', $lineKey, 2);
        $productId = filter_var($parts[0] ?? '', FILTER_VALIDATE_INT);

        if ($productId === false || $productId <= 0) {
            return [null, ''];
        }

        return [$productId, $parts[1] ?? ''];
    }
}
