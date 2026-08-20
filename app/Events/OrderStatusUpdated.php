<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired only when an admin explicitly ticks "notify customer" on a status change — see Admin\OrderController. */
class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
