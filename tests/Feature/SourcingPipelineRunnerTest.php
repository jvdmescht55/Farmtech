<?php

namespace Tests\Feature;

use App\Services\SourcingPipelineRunner;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/** Fakes the Node process itself — proves the RESULT_JSON parsing logic, not the real pipeline (that's worker/test/'s job). */
class SourcingPipelineRunnerTest extends TestCase
{
    public function test_parses_a_result_json_line_from_process_output(): void
    {
        $summary = [
            'counts' => ['passed' => 1, 'warned' => 0, 'failed' => 0, 'errored' => 0],
            'items' => [
                ['sku' => 'FT-TEST-0001', 'productId' => 42, 'slug' => 'test-product', 'title' => 'Test Product', 'verdict' => 'PASS', 'status' => 'pending_review'],
            ],
        ];

        Process::fake([
            '*' => Process::result(output: "Loaded 1 listing(s)\n  Inserted product #42\nRESULT_JSON:".json_encode($summary)."\n"),
        ]);

        $result = (new SourcingPipelineRunner())->run([
            ['sku' => 'FT-TEST-0001', 'raw_title' => 'Test Product'],
        ]);

        $this->assertTrue($result->parsed);
        $this->assertCount(1, $result->items);
        $this->assertSame(42, $result->items[0]['productId']);
        $this->assertCount(1, $result->pendingReviewItems());
        $this->assertCount(0, $result->rejectedItems());
    }

    public function test_deletes_the_temp_listing_file_after_running(): void
    {
        $capturedPath = null;
        Process::fake(function ($process) use (&$capturedPath) {
            foreach ($process->command as $part) {
                if (is_string($part) && str_ends_with($part, '.json')) {
                    $capturedPath = $part;
                }
            }

            return Process::result(output: 'RESULT_JSON:{"counts":{},"items":[]}');
        });

        (new SourcingPipelineRunner())->run([['sku' => 'FT-TEST-0002']]);

        $this->assertNotNull($capturedPath);
        $this->assertFileDoesNotExist($capturedPath);
    }

    public function test_throws_with_raw_output_when_process_fails_and_produces_no_summary(): void
    {
        Process::fake([
            '*' => Process::result(output: '', errorOutput: 'node: command not found', exitCode: 127),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('node: command not found');

        (new SourcingPipelineRunner())->run([['sku' => 'FT-TEST-0003']]);
    }

    public function test_reports_unparsed_when_process_succeeds_but_prints_no_summary(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Loaded 0 listing(s)'),
        ]);

        $result = (new SourcingPipelineRunner())->run([['sku' => 'FT-TEST-0004']]);

        $this->assertFalse($result->parsed);
        $this->assertSame([], $result->items);
    }
}
