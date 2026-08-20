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
        'sku', 'title', 'slug', 'category', 'short_description', 'description_html',
        'original_price_usd', 'est_weight_kg', 'hs_code', 'customs_duty_rate', 'vat_rate',
        'landed_cost_zar', 'retail_price_zar', 'profit_margin_pct',
        'stock_status', 'lead_time_days', 'status', 'is_active',
        'stock_quantity', 'allow_backorder', 'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'original_price_usd' => 'decimal:2',
            'est_weight_kg' => 'decimal:3',
            'customs_duty_rate' => 'decimal:4',
            'vat_rate' => 'decimal:4',
            'landed_cost_zar' => 'decimal:2',
            'retail_price_zar' => 'decimal:2',
            'profit_margin_pct' => 'decimal:2',
            'is_active' => 'boolean',
            'category' => ProductCategory::class,
            'stock_quantity' => 'integer',
            'allow_backorder' => 'boolean',
            'low_stock_threshold' => 'integer',
        ];
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

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
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
