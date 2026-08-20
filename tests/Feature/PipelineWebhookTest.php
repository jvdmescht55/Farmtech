<?php

namespace Tests\Feature;

use App\Jobs\ProcessScrapedBatchJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PipelineWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'products' => [
                [
                    'title' => 'Handheld ISO 134.2kHz RFID Stick Reader',
                    'category_hint' => 'rfid',
                    'price_usd' => 68.00,
                    'weight_kg' => 0.35,
                    'duty_rate' => 0.10,
                    'specs_table' => ['Operating Frequency' => '134.2 kHz', 'Protocol' => 'ISO 11784/11785'],
                    'images' => ['https://example.com/reader.jpg'],
                    'supplier_meta' => [
                        'name' => 'Hangzhou Xinyuan Animal ID Technology Co., Ltd.',
                        'years' => 6,
                        'is_verified' => true,
                        'has_trade_assurance' => true,
                    ],
                ],
            ],
        ];
    }

    public function test_webhook_is_disabled_when_no_secret_is_configured(): void
    {
        Config::set('services.pipeline_webhook.secret', null);

        $response = $this->postJson('/api/pipeline/webhook', $this->validPayload(), [
            'X-Pipeline-Secret' => 'anything',
        ]);

        $response->assertStatus(503);
    }

    public function test_webhook_rejects_a_wrong_secret(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');

        $response = $this->postJson('/api/pipeline/webhook', $this->validPayload(), [
            'X-Pipeline-Secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_a_missing_secret_header(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');

        $response = $this->postJson('/api/pipeline/webhook', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_a_malformed_payload(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');
        Queue::fake();

        $response = $this->postJson('/api/pipeline/webhook', [
            'products' => [
                ['title' => 'Missing everything else'],
            ],
        ], ['X-Pipeline-Secret' => 'the-real-secret']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'products.0.category_hint', 'products.0.price_usd', 'products.0.weight_kg',
            'products.0.duty_rate', 'products.0.specs_table', 'products.0.supplier_meta',
        ]);
        Queue::assertNothingPushed();
    }

    public function test_webhook_rejects_an_invalid_category_hint(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');
        Queue::fake();

        $payload = $this->validPayload();
        $payload['products'][0]['category_hint'] = 'not-a-real-category';

        $response = $this->postJson('/api/pipeline/webhook', $payload, ['X-Pipeline-Secret' => 'the-real-secret']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products.0.category_hint']);
    }

    public function test_webhook_accepts_a_valid_batch_and_queues_the_job(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');
        Queue::fake();

        $response = $this->postJson('/api/pipeline/webhook', $this->validPayload(), [
            'X-Pipeline-Secret' => 'the-real-secret',
        ]);

        $response->assertStatus(202);
        $response->assertJson(['queued' => 1]);

        Queue::assertPushed(ProcessScrapedBatchJob::class, function (ProcessScrapedBatchJob $job) {
            $listing = $job->listings[0];

            return $listing['raw_title'] === 'Handheld ISO 134.2kHz RFID Stick Reader'
                && $listing['category_hint'] === 'rfid'
                && $listing['supplier_price_usd'] === 68.0
                && $listing['duty_rate'] === 0.10
                && $listing['supplier_name'] === 'Hangzhou Xinyuan Animal ID Technology Co., Ltd.'
                && $listing['is_verified_supplier'] === true
                && $listing['image_urls'] === ['https://example.com/reader.jpg']
                && str_contains($listing['raw_specs_text'], 'Operating Frequency: 134.2 kHz.')
                && str_contains($listing['raw_specs_text'], 'Protocol: ISO 11784/11785.')
                && str_starts_with($listing['sku'], 'SCR-');
        });
    }

    public function test_webhook_accepts_a_string_specs_table_unchanged(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');
        Queue::fake();

        $payload = $this->validPayload();
        $payload['products'][0]['specs_table'] = 'Frequency: 134.2kHz. Reading distance: 25-45cm.';

        $this->postJson('/api/pipeline/webhook', $payload, ['X-Pipeline-Secret' => 'the-real-secret'])
            ->assertStatus(202);

        Queue::assertPushed(ProcessScrapedBatchJob::class, fn (ProcessScrapedBatchJob $job) =>
            $job->listings[0]['raw_specs_text'] === 'Frequency: 134.2kHz. Reading distance: 25-45cm.');
    }

    public function test_webhook_caps_batch_size_at_200(): void
    {
        Config::set('services.pipeline_webhook.secret', 'the-real-secret');
        Queue::fake();

        $payload = $this->validPayload();
        $item = $payload['products'][0];
        $payload['products'] = array_fill(0, 201, $item);

        $response = $this->postJson('/api/pipeline/webhook', $payload, ['X-Pipeline-Secret' => 'the-real-secret']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products']);
    }
}
