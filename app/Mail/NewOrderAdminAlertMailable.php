<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to config('farmtech.admin_notification_email') when an order's payment_status flips to paid. */
class NewOrderAdminAlertMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New paid order — {$this->order->order_number} (R".number_format((float) $this->order->total_zar, 0, '', ' ').')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.admin-alert',
            with: ['order' => $this->order->load('items')],
        );
    }
}
