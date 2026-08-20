<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to config('farmtech.admin_notification_email') after a scraper webhook batch produces at least one pending_review product. */
class ScrapedBatchSummaryMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array<string, mixed>> $pending
     * @param array<int, array<string, mixed>> $rejected
     * @param array<int, array<string, mixed>> $errored
     */
    public function __construct(
        public array $pending,
        public array $rejected = [],
        public array $errored = [],
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->pending);

        return new Envelope(
            subject: "{$count} new product".($count === 1 ? '' : 's')." sourced and awaiting review",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pipeline.batch-summary',
            with: [
                'pending' => $this->pending,
                'rejected' => $this->rejected,
                'errored' => $this->errored,
            ],
        );
    }
}
