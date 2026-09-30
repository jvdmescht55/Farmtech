<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


$apiKey = env('GEMINI_API_KEY');
$model = env('GEMINI_MODEL', 'gemini-2.5-flash');

if (!$apiKey) {
    echo "ERROR: GEMINI_API_KEY not found in .env\n";
    exit(1);
}


$products = Product::where('status', 'pending_review')->get();
echo "Found " . $products->count() . " pending products to enrich using model [" . $model . "].\n\n";

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $apiKey;


foreach ($products as $i => $product) {
    $num = $i + 1;
    echo "[" . $num . "/" . $products->count() . "] Enriching: " . Str::limit($product->title, 45) . "...\n";

    $prompt = "You are a commercial agritech & industrial hardware e-commerce copywriter in South Africa.\n" .
        "Raw Title: \"" . $product->title . "\"\n" .
        "Category: \"" . $product->category->value . "\"\n\n" .
        "Rewrite into valid JSON that cuts all AliExpress/SEO spam and gives clean SA commercial copy.\n" .
        "Required JSON fields:\n" .
        "1. \"title\": Clean, professional commercial title (5-10 words).\n" .
        "2. \"short_description\": 2-3 clear sentences explaining exactly what the device does, how it works, and its SA commercial/farm use case.\n" .
        "3. \"manifest\": Array of exact items typically included in the box (e.g. [\"1x Sensor Probe\", \"1x 2m Signal Cable\", \"1x User Manual\"]).\n\n" .
        "Output ONLY valid JSON matching this zone: {\"title\": \"...\", \"short_description\": \"...\", \"manifest\": [\"item 1\", \"item 2\"]}";


    try {
        $response = Http::timeout(30)->post($endpoint, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.2
            ]
        ]);

        if (!$response->successful()) {
            echo "  -> API Error: " . $response->status() . " " . Str::limit($response->body(), 80) . "\n";
            continue;
        }


        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        $data = json_decode($text, true);


        if (isset($data['title'])) {
            $product->title = $data['title'];
            $product->slug = Str::slug($data['title']) . '-' . $product->id;
            $product->short_description = $data['short_description'] ?? $product->short_description;
            $product->description = ($data['short_description'] ?? $product->short_description);
            if (isset($data['manifest']) && is_array($data['manifest'])) {
                $product->equipment_manifest = implode("\n", $data['manifest']);
            }
            $product->save();
            echo "  ✇ New Title: " . $data['title'] . "\n";
        }
    } catch (\Throwable $e) {
        echo "  -> Error: " . $e->getMessage() . "\n";
    }

    usleep(200000);
}

echo "\nEnrichment complete!\n";
