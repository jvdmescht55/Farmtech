<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'title_snapshot', 'unit_price_zar', 'quantity', 'line_total_zar',
        'variant_key', 'variant_attributes', 'variant_sku',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_zar' => 'decimal:2',
            'line_total_zar' => 'decimal:2',
            'variant_attributes' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
