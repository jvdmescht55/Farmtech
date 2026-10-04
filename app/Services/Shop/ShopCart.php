<?php

namespace App\Services\Shop;

use App\Models\StoreListing;
use Illuminate\Support\Collection;

/**
 * Session cart for the Farmtech shop (store listings). Lines for products that
 * are sold out or not out yet become reservations: shown with their price, but
 * nothing is due until the next batch is ready.
 */
class ShopCart
{
    private const KEY = 'shop_cart';

    /** @return array<int,int> listing id => qty */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(StoreListing $listing, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$listing->id] = min($listing->maxQty(), ($cart[$listing->id] ?? 0) + max(1, $qty));
        session([self::KEY => $cart]);
    }

    public function set(StoreListing $listing, int $qty): void
    {
        $cart = $this->raw();
        if ($qty < 1) {
            unset($cart[$listing->id]);
        } else {
            $cart[$listing->id] = min($listing->maxQty(), $qty);
        }
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<int, array{listing: StoreListing, qty: int, reserve: bool, unit: int, line: int}> */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }

        return StoreListing::published()->whereIn('id', array_keys($raw))->get()
            ->map(fn (StoreListing $l) => [
                'listing' => $l,
                'qty' => $q = min($raw[$l->id], $l->maxQty()),
                'reserve' => $l->reservable(),
                'unit' => (int) $l->price_cents, // 0 = price confirmed when the batch is ready
                'line' => (int) $l->price_cents * $q,
            ])->values();
    }

    /** What's paid now (in-stock lines). */
    public function dueNow(?Collection $lines = null): int
    {
        return ($lines ?? $this->lines())->where('reserve', false)->sum('line');
    }

    /** What's reserved for the next batch (paid later). */
    public function reserved(?Collection $lines = null): int
    {
        return ($lines ?? $this->lines())->where('reserve', true)->sum('line');
    }
}
