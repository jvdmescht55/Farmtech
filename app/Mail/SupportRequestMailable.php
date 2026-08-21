<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A real customer support form submission, sent to config('farmtech.admin_notification_email'). */
class SupportRequestMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $topic,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Support request — {$this->topic}",
            replyTo: [$this->senderEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support.request',
            with: [
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'topic' => $this->topic,
                'messageBody' => $this->message,
            ],
        );
    }
}
