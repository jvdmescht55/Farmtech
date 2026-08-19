<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'email', 'phone',
        'address_line1', 'address_line2', 'city', 'province', 'postal_code',
        'subtotal_zar', 'shipping_zar', 'total_zar',
        'payment_gateway', 'payment_status', 'payment_reference', 'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_zar' => 'decimal:2',
            'shipping_zar' => 'decimal:2',
            'total_zar' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'FT-'.strtoupper(Str::random(8));
            }
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
