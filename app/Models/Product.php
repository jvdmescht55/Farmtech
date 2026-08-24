<?php

namespace App\Models;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku', 'title', 'slug', 'category', 'short_description', 'description_html', 'included_items',
        'compatibility_notes', 'requirements_notes', 'warranty_terms',
        'specifications', 'key_features', 'variants', 'brand_name', 'model_number', 'warranty_period',
        'original_price_usd', 'est_weight_kg', 'gross_weight_kg', 'package_dimensions', 'hs_code', 'customs_duty_rate', 'vat_rate',
        'intl_freight_zar', 'customs_vat_zar', 'domestic_delivery_zar', 'customs_clearance_zar',
        'landed_cost_zar', 'retail_price_zar', 'profit_margin_pct',
        'supplier_cost_usd', 'exchange_rate', 'supplier_name', 'supplier_url', 'source_url', 'supplier_last_checked_at',
        'import_contingency_pct', 'insurance_cost_zar', 'payment_fees_zar',
        'stock_status', 'stock_availability_type', 'lead_time_days', 'status', 'is_active',
        'stock_quantity', 'allow_backorder', 'low_stock_threshold',
        'verification_tier', 'icasa_status', 'radio_frequency_confirmed', 'datasheet_uploaded',
        'published_at', 'approved_at', 'auto_publish_checked_at', 'published_via',
    ];

    protected function casts(): array
    {
        return [
            'included_items' => 'array',
            'specifications' => 'array',
            'key_features' => 'array',
            'variants' => 'array',
            'original_price_usd' => 'decimal:2',
            'est_weight_kg' => 'decimal:3',
            'gross_weight_kg' => 'decimal:3',
            'customs_duty_rate' => 'decimal:4',
            'vat_rate' => 'decimal:4',
            'intl_freight_zar' => 'decimal:2',
            'customs_vat_zar' => 'decimal:2',
            'domestic_delivery_zar' => 'decimal:2',
            'customs_clearance_zar' => 'decimal:2',
            'landed_cost_zar' => 'decimal:2',
            'retail_price_zar' => 'decimal:2',
            'supplier_cost_usd' => 'decimal:2',
            'exchange_rate' => 'decimal:2',
            'supplier_last_checked_at' => 'datetime',
            'import_contingency_pct' => 'decimal:2',
            'insurance_cost_zar' => 'decimal:2',
            'payment_fees_zar' => 'decimal:2',
            'profit_margin_pct' => 'decimal:2',
            'is_active' => 'boolean',
            'category' => ProductCategory::class,
            'stock_quantity' => 'integer',
            'allow_backorder' => 'boolean',
            'low_stock_threshold' => 'integer',
            'radio_frequency_confirmed' => 'boolean',
            'datasheet_uploaded' => 'boolean',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'auto_publish_checked_at' => 'datetime',
        ];
    }

    /** "Your Cut" — net gross profit in ZAR, retail price minus everything it cost to land the item. */
    public function getNetProfitZarAttribute(): ?float
    {
        if ($this->retail_price_zar === null || $this->landed_cost_zar === null) {
            return null;
        }

        return round((float) $this->retail_price_zar - (float) $this->landed_cost_zar, 2);
    }

    public function getNetProfitMarginPctAttribute(): ?float
    {
        if (! $this->retail_price_zar || (float) $this->retail_price_zar <= 0 || $this->net_profit_zar === null) {
            return null;
        }

        return round(($this->net_profit_zar / (float) $this->retail_price_zar) * 100, 2);
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = static::uniqueSlug($product->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }

    public function specs()
    {
        return $this->hasMany(ProductSpec::class)->orderBy('spec_group')->orderBy('id');
    }

    public function hasVariants(): bool
    {
        return !empty($this->variants);
    }

    /**
     * Deterministic key for a variant's attribute combination — sorted by
     * attribute name so {"Power":"50W","Color":"Black"} and
     * {"Color":"Black","Power":"50W"} produce the same key. Must match the
     * JS `variantKey()` helper in products/show.blade.php exactly, since
     * the cart/add-to-cart form is keyed on this same string from both
     * sides (server-rendered PHP and the client-side Alpine selector).
     */
    public static function variantKey(array $attributes): string
    {
        ksort($attributes);

        $parts = [];
        foreach ($attributes as $name => $value) {
            $parts[] = "{$name}={$value}";
        }

        return implode('|', $parts);
    }

    /**
     * Option groups derived from the variants array for the storefront
     * selector UI — e.g. {"Color": ["Black", "White"], "Power": ["50W", "100W"]}.
     * Order preserved (first-seen), not alphabetical, so the admin's
     * scrape-order intent survives into the UI.
     */
    public function variantOptionGroups(): array
    {
        $groups = [];

        foreach ($this->variants ?? [] as $variant) {
            foreach (($variant['attributes'] ?? []) as $name => $value) {
                $groups[$name] ??= [];
                if (!in_array($value, $groups[$name], true)) {
                    $groups[$name][] = $value;
                }
            }
        }

        return $groups;
    }

    /** The variant row (attributes/sku_suffix/price_delta_zar) matching a given attribute selection, if any. */
    public function findVariant(array $attributes): ?array
    {
        return $this->findVariantByKey(static::variantKey($attributes));
    }

    /** Same lookup as findVariant(), but from an already-computed variantKey() string (e.g. from a cart line). */
    public function findVariantByKey(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        foreach ($this->variants ?? [] as $variant) {
            if (static::variantKey($variant['attributes'] ?? []) === $key) {
                return $variant;
            }
        }

        return null;
    }

    public function highlightSpecs()
    {
        return $this->hasMany(ProductSpec::class)->where('is_highlight', true);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function thumbnail()
    {
        return $this->hasOne(ProductImage::class)->where('is_thumbnail', true);
    }

    public function complianceAudit()
    {
        return $this->hasOne(ComplianceAudit::class)->latestOfMany();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Real, admin-curated "Frequently Bought Together" companions — never inferred. */
    public function bundleCompanions()
    {
        return $this->belongsToMany(Product::class, 'product_bundle_items', 'product_id', 'companion_product_id');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /** All products across every ProductCategory that belongs to the given Industry. */
    public function scopeIndustry(Builder $query, \App\Enums\Industry $industry): Builder
    {
        $values = array_map(fn (\App\Enums\ProductCategory $c) => $c->value, $industry->categories());

        return $query->whereIn('category', $values);
    }

    /**
     * "Top 5 Trending" — ranked by real units actually sold (order_items.quantity),
     * not a fabricated view/conversion metric we don't track. Products tied on zero
     * sales (the common case in a young catalog) fall back to newest-first, so the
     * strip is never empty just because nothing has sold yet.
     */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->storefrontVisible()
            ->withSum('orderItems as units_sold', 'quantity')
            ->orderByDesc('units_sold')
            ->orderByDesc('created_at');
    }

    public function scopeStorefrontVisible(Builder $query): Builder
    {
        return $query->where('status', 'approved')->where('is_active', true);
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category->label();
    }

    /** Null stock_quantity means "not tracked" — unlimited, the default for every product until an admin sets a real count. */
    public function isTracked(): bool
    {
        return $this->stock_quantity !== null;
    }

    public function isLowStock(): bool
    {
        return $this->isTracked() && $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->isTracked() && $this->stock_quantity <= 0 && ! $this->allow_backorder;
    }

    public function canFulfill(int $quantity): bool
    {
        if (! $this->isTracked()) {
            return true;
        }

        return $this->allow_backorder || $this->stock_quantity >= $quantity;
    }

    /**
     * Atomic, race-condition-safe decrement: a single UPDATE with the
     * sufficiency check baked into its WHERE clause (not a separate
     * SELECT-then-UPDATE, which two concurrent checkouts could both pass).
     * Returns false if another request already claimed the last unit(s) and
     * this product isn't backorderable — the caller should roll back the
     * whole order transaction in that case. No-op (always true) for
     * untracked products.
     */
    public function decrementStock(int $quantity): bool
    {
        if (! $this->isTracked()) {
            return true;
        }

        $affected = static::query()
            ->where('id', $this->id)
            ->where(function (Builder $query) use ($quantity) {
                $query->where('stock_quantity', '>=', $quantity)->orWhere('allow_backorder', true);
            })
            ->decrement('stock_quantity', $quantity);

        if ($affected > 0) {
            $this->stock_quantity -= $quantity;
        }

        return $affected > 0;
    }
}
