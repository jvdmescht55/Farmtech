<?php

require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$apiKey = env("GEMINI_API_KEY");
$model = env("GEMINI_MODEL", "gemini-2.5-flash");

$products = Product::where("status", "pending_review")->get();
echo "Processing " . $products->count() . " pending products in chunks of 40...\n\n";

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $apiKey;

$chunks = $products->chunk(40);

foreach ($chunks as $chunkIndex => $chunk) {
    $batchNum = $chunkIndex + 1;
    echo "[Batch $batchNum/" . $chunks->count() . "] Sending " . $chunk->count() . " products to AI...\n";

    $itemsPayload = [];
    foreach ($chunk as $p) {
        $itemsPayload[] = ["id" => $p->id, "raw_title" => $p->title, "category" => $p->category->value];
    }

    $prompt = "You are an agritech/industrial hardware copywriter in South Africa.\n" .
        "Transform these raw supplier items into clean commercial e-commerce copy.\n" .
        "Input list:\n" . json_encode($itemsPayload) . "\n\n" .
        "Return a JSON array where each object contains:\n" .
        "- id (integer matching input)\n" .
        "- title (clean commercial title 5-9 words)\n" .
        "- short_description (2-3 clear sentences explaining function and commercial farm/fleet use in SA)\n" .
        "- included_items (array of strings for box contents)\n\n" .
        "Strictly output JSON only:";

    try {
        $res = Http::timeout(60)->post($endpoint, [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["responseMimeType" => "application/json", "temperature" => 0.2]
        ]);

        if ($res->successful()) {
            $body = $res->json();
            $text = $body["candidates"][0]["content"]["parts"][0]["text"] ?? "";
            $data = json_decode(trim(preg_replace("/^```[a-z]*\n?/i", "", preg_replace("/\n?```$/i", "", $text))), true);

            if (is_array($data)) {
                foreach ($data as $item) {
                    if (isset($item["id"]) && isset($item["title"])) {
                        $prod = Product::find($item["id"]);
                        if ($prod) {
                            $prod->title = $item["title"];
                            $prod->slug = Str::slug($item["title"]) . "-" . $prod->id;
                            $prod->short_description = $item["short_description"] ?? $prod->short_description;
                            if (!empty($item["included_items"]) && is_array($item["included_items"])) {
                                $prod->included_items = implode("\n", $item["included_items"]);
                            }
                            $prod->save();
                            echo "  ✓ [#" . $prod->id . "] " . $item["title"] . "\n";
                        }
                    }
                }
            }
        } else {
            echo "  - Error status: " . $res->status() . "\n";
        }
    } catch (\Throwable $e) {
        echo "  - Exception: " . $e->getMessage() . "\n";
    }

    sleep(4);
}

echo "\nFinished polishing catalog!\n";
