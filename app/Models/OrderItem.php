<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'title_snapshot', 'unit_price_zar', 'quantity', 'line_total_zar',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_zar' => 'decimal:2',
            'line_total_zar' => 'decimal:2',
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
