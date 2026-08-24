<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PolishProductCopy extends Command
{
    protected $signature = 'products:polish {--sku= : Only polish the product with this SKU} {--limit= : Only polish the first N pending products}';
    protected $description = 'Uses AI to rewrite raw supplier titles/descriptions/manifests, grounded in the real scraped specifications.';

    public function handle(): int
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = config('services.gemini.model');

        if (!$apiKey) {
            $this->error('GEMINI_API_KEY is missing in .env');
            return 1;
        }

        $query = Product::where('status', 'pending_review');

        if ($sku = $this->option('sku')) {
            $query->where('sku', $sku);
        }

        $products = $query->get();

        if ($limit = $this->option('limit')) {
            $products = $products->take((int) $limit);
        }

        $total = $products->count();
        $this->info("Starting AI enrichment for {$total} pending products using model [{$model}]...");

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        foreach ($products as $i => $product) {
            $num = $i + 1;
            $this->line("[{$num}/{$total}] Processing: " . Str::limit($product->title, 45));

            $prompt = $this->buildPrompt($product);

            try {
                $res = Http::timeout(30)->post($endpoint, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.2],
                ]);

                if ($res->successful()) {
                    $body = $res->json();
                    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    $data = $text ? json_decode($text, true) : null;

                    if (is_array($data) && !empty($data['title'])) {
                        $product->title = $data['title'];
                        $product->slug = Str::slug($data['title']) . '-' . $product->id;
                        $product->short_description = $data['short_description'] ?? $product->short_description;
                        $product->description_html = $data['description_html'] ?? $product->description_html;

                        if (!empty($data['manifest']) && is_array($data['manifest'])) {
                            $product->included_items = $data['manifest'];
                        }
                        if (!empty($data['key_features']) && is_array($data['key_features'])) {
                            $product->key_features = $data['key_features'];
                        }

                        $product->save();
                        $this->info('  -> New: ' . $data['title']);
                    } else {
                        $this->warn('  - AI response missing usable JSON, skipping.');
                    }
                } else {
                    $this->warn('  - Could not fetch AI response: ' . $res->status() . ' ' . Str::limit($res->body(), 200));
                }
            } catch (\Throwable $e) {
                $this->warn('  - Error: ' . $e->getMessage());
            }

            usleep(250000);
        }

        $this->info('All products enriched.');
        return 0;
    }

    private function buildPrompt(Product $product): string
    {
        $specifications = $product->specifications ?: [];
        $specsJson = json_encode($specifications, JSON_PRETTY_PRINT);
        $includedItems = $product->included_items ? implode(', ', $product->included_items) : 'not confirmed by supplier';

        return "You are a South African commercial/agricultural hardware copywriter for Farmtech, an import reseller.\n" .
            "Raw Title: \"{$product->title}\"\n" .
            "Category: \"{$product->category->value}\"\n" .
            'Brand: "' . ($product->brand_name ?? 'unbranded') . '"  Model: "' . ($product->model_number ?? 'n/a') . "\"\n" .
            'Supplier-stated warranty: "' . ($product->warranty_period ?? 'not stated') . "\"\n" .
            "Currently recorded included items: {$includedItems}\n" .
            "Raw scraped technical specifications (JSON, verbatim from the supplier listing — do not invent " .
            "any spec not present here):\n{$specsJson}\n\n" .
            "Output VALID JSON only, no markdown fences, with these exact keys:\n" .
            "1. \"title\": Clean, professional commercial title (5-10 words), no SEO fluff, no ALL CAPS.\n" .
            "2. \"short_description\": 2-3 precise sentences on what it does and how it works.\n" .
            "3. \"description_html\": A few short paragraphs (plain <p> and one <ul> of key selling points " .
            "allowed) written as a DETAILED, South-African-agriculture-tailored functional use case — describe " .
            "a concrete on-farm scenario this device solves, using only facts present in the specifications " .
            "above. Include, if present in the specifications, the power/operating requirements (voltage, " .
            "battery type/capacity, charging method) stated plainly so a buyer knows what they need to run it.\n" .
            "4. \"manifest\": Array of exact items included in the box, derived ONLY from probe/accessory-style " .
            "spec entries (e.g. \"Probe options\") and the currently recorded included items above — do not " .
            "invent accessories not evidenced in the data (e.g. [\"1x Main Unit\", \"1x Rectal Probe\", " .
            "\"1x Carry Case\", \"1x Charging Cable\", \"1x User Manual\"]).\n" .
            "5. \"key_features\": Array of 3-6 short (under 10 words) farmer-facing highlight bullets drawn " .
            "from the specifications (e.g. \"2600mAh removable battery for all-day field use\").\n\n" .
            "Keep every technical claim consistent with the specifications JSON given — never fabricate a " .
            "spec, certification, or capability not present there.\n\n" .
            '{"title":"...","short_description":"...","description_html":"...","manifest":["..."],"key_features":["..."]}';
    }
}
