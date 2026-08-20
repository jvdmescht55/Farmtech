<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\NewOrderAdminAlertMailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendNewOrderAdminAlert implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        Mail::to(config('farmtech.admin_notification_email'))->send(new NewOrderAdminAlertMailable($event->order));
    }
}
