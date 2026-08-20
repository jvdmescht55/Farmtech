<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'email', 'phone',
        'address_line1', 'address_line2', 'city', 'province', 'postal_code',
        'billing_same_as_shipping', 'billing_address_line1', 'billing_address_line2',
        'billing_city', 'billing_province', 'billing_postal_code',
        'subtotal_zar', 'shipping_zar', 'total_zar',
        'payment_gateway', 'payment_status', 'payment_reference', 'status',
        'tracking_number', 'courier_name',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_zar' => 'decimal:2',
            'shipping_zar' => 'decimal:2',
            'total_zar' => 'decimal:2',
            'billing_same_as_shipping' => 'boolean',
            'status' => OrderStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * FT-YYYYMMDD-XXXX, sequential per day. Accepts the tiny race-condition
     * risk of two orders landing on the same count under real concurrent
     * checkouts — fine at this store's scale, not fine at high volume (see
     * PROGRESS.md).
     */
    public static function generateOrderNumber(): string
    {
        $datePart = now()->format('Ymd');
        $todayCount = static::whereDate('created_at', now()->toDateString())->count();

        return "FT-{$datePart}-".str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class)->latest();
    }
}
