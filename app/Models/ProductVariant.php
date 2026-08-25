<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'option_name', 'supplier_cost_usd',
        'landed_cost_zar', 'retail_price_zar', 'is_default', 'stock_quantity',
    ];

    protected function casts(): array
    {
        return [
            'supplier_cost_usd' => 'decimal:2',
            'landed_cost_zar' => 'decimal:2',
            'retail_price_zar' => 'decimal:2',
            'is_default' => 'boolean',
            'stock_quantity' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** "Your Cut" for this specific variant — same principle as Product::getNetProfitZarAttribute(). */
    public function getEstProfitZarAttribute(): ?float
    {
        if ($this->retail_price_zar === null || $this->landed_cost_zar === null) {
            return null;
        }

        return round((float) $this->retail_price_zar - (float) $this->landed_cost_zar, 2);
    }
}
