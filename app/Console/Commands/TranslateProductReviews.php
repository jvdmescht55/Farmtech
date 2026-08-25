<?php

namespace App\Console\Commands;

use App\Models\ProductReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * AI cleanup/translation pass over real imported Alibaba reviews
 * (ProductReview::review_text, from worker/lib_pricing.php's
 * ft_extract_reviews()). Real buyer feedback, some of it non-English
 * (Spanish/Portuguese/French/Russian confirmed in the actual scrape
 * corpus) or written into Alibaba's own guided-review template
 * ("Delivery： ... Quality： ... Design： ... Service： ..."), which
 * doesn't read like a normal customer review on a Western storefront.
 * This translates/smooths the wording into natural English while keeping
 * every review grounded in whatever sentiment the buyer actually
 * expressed — never inventing product feedback that wasn't there. The
 * original text is preserved in original_review_text regardless (set at
 * import time, never overwritten by this command).
 */
class TranslateProductReviews extends Command
{
    protected $signature = 'products:translate-reviews {--limit=200 : Max reviews to process in one run} {--batch=25 : Reviews per Gemini call} {--live-only : Only process reviews attached to currently storefront-visible products}';
    protected $description = 'Uses AI to translate and naturalize imported Alibaba review text into clean English.';

    public function handle(): int
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');

        if (!$apiKey) {
            $this->error('GEMINI_API_KEY is missing in .env');
            return 1;
        }

        $query = ProductReview::where('text_cleaned', false)
            ->whereNotNull('review_text')
            ->where('review_text', '!=', '');

        if ($this->option('live-only')) {
            $query->whereHas('product', fn ($q) => $q->storefrontVisible());
        }

        $reviews = $query->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($reviews->isEmpty()) {
            $this->info('No reviews pending translation/cleanup.');
            return 0;
        }

        $batchSize = max(1, (int) $this->option('batch'));
        $chunks = $reviews->chunk($batchSize);
        $this->info("Processing {$reviews->count()} review(s) in {$chunks->count()} batch(es) using [{$model}]...");

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $done = 0;

        foreach ($chunks as $batchNum => $chunk) {
            $this->line('[batch ' . ($batchNum + 1) . '/' . $chunks->count() . '] ' . $chunk->count() . ' review(s)...');

            $payload = $chunk->map(fn (ProductReview $r) => [
                'id' => $r->id,
                'text' => $r->review_text,
            ])->values()->all();

            $prompt = $this->buildPrompt($payload);

            try {
                $res = Http::timeout(60)->post($endpoint, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
                ]);

                if (!$res->successful()) {
                    $this->warn('  - HTTP ' . $res->status() . ': ' . str($res->body())->limit(200));
                    continue;
                }

                $text = $res->json('candidates.0.content.parts.0.text');
                $data = $text ? json_decode($text, true) : null;

                if (!is_array($data)) {
                    $this->warn('  - No parseable JSON in AI response, skipping batch.');
                    continue;
                }

                foreach ($data as $row) {
                    if (!isset($row['id'], $row['text']) || $row['text'] === '') {
                        continue;
                    }

                    $review = $chunk->firstWhere('id', (int) $row['id']);
                    if (!$review) {
                        continue;
                    }

                    $review->update([
                        'review_text' => $row['text'],
                        'text_cleaned' => true,
                    ]);
                    $done++;
                }
            } catch (\Throwable $e) {
                $this->warn('  - Error: ' . $e->getMessage());
            }

            usleep(250000);
        }

        $this->info("Done. {$done}/{$reviews->count()} review(s) cleaned.");

        return 0;
    }

    private function buildPrompt(array $payload): string
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "You are cleaning up genuine B2B buyer reviews imported from Alibaba for display on a South African " .
            "commercial/agricultural equipment storefront.\n\n" .
            "For each review below, rewrite its \"text\" into natural, grammatically correct English:\n" .
            "- Translate if it is not already in English (any language may appear — Spanish, Portuguese, French, " .
            "Russian, etc. have all been seen in this data).\n" .
            "- If the text follows Alibaba's own guided-review template (fragments like \"Delivery： ... Quality： " .
            "... Design： ... Service： ...\"), rewrite it as one or two flowing natural sentences instead of that " .
            "labeled fragment structure.\n" .
            "- Remove any mention of the exporter's internal shipping/logistics coordination or factory-side trade " .
            "negotiation (e.g. references to placing a next factory order, production scheduling) that wouldn't " .
            "make sense as a product review on a retail storefront — but do NOT remove genuine delivery/service " .
            "feedback a normal customer would say (e.g. \"arrived quickly\", \"good communication\").\n" .
            "- NEVER invent product feedback, specs, or claims that are not implied by the original text. If the " .
            "original is only a short generic pleasantry (e.g. \"good\", \"ok\", \"nice\"), translate it plainly — " .
            "do not pad it into something longer or more specific than the source.\n" .
            "- Keep the buyer's actual sentiment (positive/negative/neutral) unchanged.\n" .
            "- Keep each result reasonably concise (roughly the same length class as the original, translated).\n\n" .
            "Input (array of {id, text}):\n{$json}\n\n" .
            'Output a JSON array of {"id": <same integer id>, "text": "<cleaned English text>"} for every input ' .
            "item, in any order, strictly valid JSON only, no markdown fences:";
    }
}
