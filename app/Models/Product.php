<?php

namespace App\Models;

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
        return match ($this->category) {
            'scales' => 'Livestock Scales & Load Cells',
            'ultrasound' => 'Veterinary Ultrasound Scanners',
            'rfid' => 'RFID Readers & Ear Tagging Systems',
            'accessories' => 'Replacement Probes & Accessories',
            default => Str::title($this->category),
        };
    }
}
