<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Mail\OrderStatusUpdatedCustomerMailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusUpdatedEmail implements ShouldQueue
{
    public function handle(OrderStatusUpdated $event): void
    {
        Mail::to($event->order->email)->send(new OrderStatusUpdatedCustomerMailable($event->order));
    }
}
