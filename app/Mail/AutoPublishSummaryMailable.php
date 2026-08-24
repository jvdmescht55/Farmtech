<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to config('farmtech.admin_notification_email') whenever products:auto-publish actually publishes something with no human review. */
class AutoPublishSummaryMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{product: \App\Models\Product, reasons: array}> $published
     * @param array<int, array{product: \App\Models\Product, reasons: array}> $capped
     */
    public function __construct(
        public array $published,
        public array $capped = [],
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->published);

        return new Envelope(
            subject: "{$count} product".($count === 1 ? '' : 's')." auto-published to the storefront",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pipeline.auto-publish-summary',
            with: [
                'published' => $this->published,
                'capped' => $this->capped,
            ],
        );
    }
}
