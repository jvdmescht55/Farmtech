<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "Your free months end in 7 days" and friends. */
class SubscriptionReminderMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $kind, public int $days, public string $until) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->kind === 'ended'
            ? 'Herd Manager is now read-only (your records are safe)'
            : 'Herd Manager: '.($this->days === 1 ? 'tomorrow' : "{$this->days} days").' until your '.($this->kind === 'trial' ? 'free months end' : 'next payment'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.billing.reminder');
    }
}
