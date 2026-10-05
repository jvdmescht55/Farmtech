<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'title_snapshot', 'unit_price_zar', 'quantity', 'line_total_zar',
        'product_variant_id', 'variant_option_name', 'variant_sku',
        'store_listing_id', 'is_reservation',
    ];

    public function listing()
    {
        return $this->belongsTo(StoreListing::class, 'store_listing_id');
    }

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


}
