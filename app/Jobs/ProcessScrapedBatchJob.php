<?php

namespace App\Jobs;

use App\Mail\ScrapedBatchSummaryMailable;
use App\Services\SourcingPipelineRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Runs one scraped batch (from POST /api/pipeline/webhook) through the same
 * Node pipeline the admin's manual "Source New Listing" form uses — AI
 * vetting, compliance filtering, landed-cost calculation — then emails an
 * admin summary if anything actually landed in the review queue.
 */
class ProcessScrapedBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    /** @param array<int, array<string, mixed>> $listings Already mapped to the pipeline's internal listing shape. */
    public function __construct(public array $listings) {}

    public function handle(SourcingPipelineRunner $runner): void
    {
        $result = $runner->run($this->listings, timeoutSeconds: max(180, count($this->listings) * 30));

        foreach ($result->erroredItems() as $item) {
            Log::warning('Scraped batch: listing failed vetting/insert', [
                'sku' => $item['sku'] ?? null,
                'title' => $item['title'] ?? null,
                'error' => $item['error'] ?? null,
            ]);
        }

        if (!$result->parsed) {
            Log::error('Scraped batch: pipeline produced no parseable summary', [
                'listing_count' => count($this->listings),
                'output' => $result->rawOutput,
            ]);

            return;
        }

        $pending = $result->pendingReviewItems();

        // Only send the "awaiting review" email when there's actually
        // something to review — a batch that's all rejects or errors
        // doesn't need to interrupt anyone, but it is still logged above.
        if ($pending !== []) {
            Mail::to(config('farmtech.admin_notification_email'))
                ->send(new ScrapedBatchSummaryMailable($pending, $result->rejectedItems(), $result->erroredItems()));
        }
    }
}
