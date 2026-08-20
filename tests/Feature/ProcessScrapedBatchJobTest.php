<?php

namespace Tests\Feature;

use App\Jobs\ProcessScrapedBatchJob;
use App\Mail\ScrapedBatchSummaryMailable;
use App\Services\PipelineRunResult;
use App\Services\SourcingPipelineRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Exercises ProcessScrapedBatchJob::handle() directly against a mocked
 * SourcingPipelineRunner — never spawns the real Node pipeline in tests,
 * same principle as PipelineTest's docblock: this proves Farmtech's own
 * code reacts correctly to a pipeline result, not that Gemini's judgment
 * is correct (that's covered by real API calls in worker/test/).
 */
class ProcessScrapedBatchJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_summary_email_when_products_are_staged_for_review(): void
    {
        Mail::fake();

        $runner = Mockery::mock(SourcingPipelineRunner::class);
        $runner->shouldReceive('run')->once()->andReturn(new PipelineRunResult(
            items: [
                ['sku' => 'SCR-AAA111', 'title' => 'Handheld RFID Reader', 'verdict' => 'PASS', 'status' => 'pending_review', 'productId' => 1],
                ['sku' => 'SCR-BBB222', 'title' => 'Cheap Knockoff Scanner', 'verdict' => 'FAIL', 'status' => 'rejected', 'productId' => 2],
            ],
            counts: ['passed' => 1, 'warned' => 0, 'failed' => 1, 'errored' => 0],
            rawOutput: 'RESULT_JSON:{}',
            parsed: true,
        ));

        $job = new ProcessScrapedBatchJob([['sku' => 'SCR-AAA111'], ['sku' => 'SCR-BBB222']]);
        $job->handle($runner);

        Mail::assertSent(ScrapedBatchSummaryMailable::class, function (ScrapedBatchSummaryMailable $mail) {
            return count($mail->pending) === 1
                && $mail->pending[0]['sku'] === 'SCR-AAA111'
                && count($mail->rejected) === 1;
        });
    }

    public function test_does_not_send_an_email_when_nothing_passed_review(): void
    {
        Mail::fake();

        $runner = Mockery::mock(SourcingPipelineRunner::class);
        $runner->shouldReceive('run')->once()->andReturn(new PipelineRunResult(
            items: [
                ['sku' => 'SCR-CCC333', 'title' => 'Non-Compliant Scanner', 'verdict' => 'FAIL', 'status' => 'rejected'],
            ],
            counts: ['passed' => 0, 'warned' => 0, 'failed' => 1, 'errored' => 0],
            rawOutput: 'RESULT_JSON:{}',
            parsed: true,
        ));

        $job = new ProcessScrapedBatchJob([['sku' => 'SCR-CCC333']]);
        $job->handle($runner);

        Mail::assertNothingSent();
    }

    public function test_logs_but_does_not_email_when_pipeline_output_is_unparseable(): void
    {
        Mail::fake();
        Log::spy();

        $runner = Mockery::mock(SourcingPipelineRunner::class);
        $runner->shouldReceive('run')->once()->andReturn(new PipelineRunResult(
            items: [],
            counts: [],
            rawOutput: 'node: command not found',
            parsed: false,
        ));

        $job = new ProcessScrapedBatchJob([['sku' => 'SCR-DDD444']]);
        $job->handle($runner);

        Mail::assertNothingSent();
        Log::shouldHaveReceived('error')->once();
    }

    public function test_errored_items_are_logged_individually(): void
    {
        Mail::fake();
        Log::spy();

        $runner = Mockery::mock(SourcingPipelineRunner::class);
        $runner->shouldReceive('run')->once()->andReturn(new PipelineRunResult(
            items: [
                ['sku' => 'SCR-EEE555', 'title' => 'Broken Listing', 'error' => 'Gemini response was not valid JSON'],
            ],
            counts: ['passed' => 0, 'warned' => 0, 'failed' => 0, 'errored' => 1],
            rawOutput: 'RESULT_JSON:{}',
            parsed: true,
        ));

        $job = new ProcessScrapedBatchJob([['sku' => 'SCR-EEE555']]);
        $job->handle($runner);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn ($message, $context) => $context['sku'] === 'SCR-EEE555'
        );
    }
}
