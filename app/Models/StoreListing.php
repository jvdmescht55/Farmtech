<?php

namespace App\Models;

use App\Support\SiteImages;
use Illuminate\Database\Eloquent\Model;

/**
 * Farmtech's own products (KraalTrac Pro, KraalTrac Watch, ear tags…), written and
 * published from admin. Stock drives what the shop offers: buy now, or reserve
 * a unit from the next batch when it's sold out / not out yet.
 */
class StoreListing extends Model
{
    public const STOCK = [
        'in_stock' => 'In stock',
        'low_stock' => 'Only a few left',
        'sold_out' => 'Sold out: reserve from the next batch',
        'coming_soon' => 'Coming soon: reserve yours',
    ];

    protected $fillable = ['slug', 'name', 'tagline', 'category', 'price_cents', 'unit_label', 'compare_at_cents', 'badge', 'availability', 'stock_status', 'stock_qty', 'max_per_order', 'next_batch', 'module', 'overview', 'features', 'specs', 'in_box', 'why', 'images', 'photo_key', 'is_published', 'sort'];

    protected function casts(): array
    {
        return ['features' => 'array', 'specs' => 'array', 'in_box' => 'array', 'images' => 'array', 'is_published' => 'boolean'];
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true)->orderBy('sort')->orderBy('name');
    }

    public function priceLabel(): ?string
    {
        return $this->price_cents === null ? null : self::rand($this->price_cents);
    }

    public static function rand(int $cents): string
    {
        return 'R'.number_format($cents / 100, $cents % 100 ? 2 : 0, '.', ' ');
    }

    /** Can be bought and paid for now. */
    public function buyable(): bool
    {
        return $this->price_cents !== null && in_array($this->stock_status, ['in_stock', 'low_stock'], true)
            && ($this->stock_qty === null || $this->stock_qty > 0);
    }

    /** Sold out or not out yet: people reserve one from the next batch (no payment yet). */
    public function reservable(): bool
    {
        return ! $this->buyable();
    }

    public function stockLabel(): string
    {
        if ($this->stock_status === 'low_stock' && $this->stock_qty) {
            return "Only {$this->stock_qty} left";
        }
        if ($this->stock_qty === 0 && in_array($this->stock_status, ['in_stock', 'low_stock'], true)) {
            return self::STOCK['sold_out'];
        }

        return self::STOCK[$this->stock_status] ?? 'In stock';
    }

    public function maxQty(): int
    {
        $max = $this->max_per_order ?: 20;

        return $this->buyable() && $this->stock_qty !== null ? max(1, min($max, $this->stock_qty)) : $max;
    }

    /** First uploaded photo, else the site photo chosen for it. */
    public function photoUrl(bool $small = true): ?string
    {
        if ($this->images) {
            return asset('storage/'.$this->images[0]);
        }

        return $this->photo_key && isset(SiteImages::IMAGES[$this->photo_key]) ? SiteImages::url($this->photo_key, $small) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
