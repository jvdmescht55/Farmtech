<?php

namespace App\Console\Commands;

use App\Mail\AutoPublishSummaryMailable;
use App\Models\Product;
use App\Models\Setting;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Publishes an already-staged pending_review product to the live storefront
 * with no human involved, but only when Gemini has no real doubt at all
 * (worker/src/autoPublishCheck.js — a much higher bar than normal listing
 * vetting) AND the product's category isn't already at its configured live
 * cap (Setting::auto_publish_cap_per_category, default 2 — deliberately
 * keeps any one category from filling up with unreviewed listings).
 *
 * Scheduled hourly (routes/console.php). Every candidate gets
 * auto_publish_checked_at stamped whether it qualifies or not, so a
 * standing pending_review backlog isn't re-billed against Gemini every run
 * — see the 24h re-check window below.
 */
class AutoPublishProducts extends Command
{
    protected $signature = 'products:auto-publish {--limit=20 : Max candidates to check in one run}';
    protected $description = 'Asks Gemini whether any pending_review products are complete/consistent enough to publish with no human review, subject to a per-category live cap.';

    public function handle(): int
    {
        $capPerCategory = (int) Setting::get('auto_publish_cap_per_category', 2);

        if ($capPerCategory <= 0) {
            $this->info('auto_publish_cap_per_category is 0 — auto-publish is disabled. Nothing to do.');
            return 0;
        }

        $candidates = Product::query()
            ->where('status', 'pending_review')
            ->where(function ($q) {
                $q->whereNull('auto_publish_checked_at')
                    ->orWhere('auto_publish_checked_at', '<', now()->subDay());
            })
            ->orderBy('created_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No pending_review products due for an auto-publish check.');
            return 0;
        }

        $this->info("Checking {$candidates->count()} candidate(s) with Gemini...");

        $verdicts = $this->runAiCheck($candidates);

        $published = [];
        $capped = [];

        foreach ($candidates as $product) {
            $verdict = $verdicts[$product->id] ?? null;
            $product->auto_publish_checked_at = now();

            if (!$verdict) {
                $this->warn("  #{$product->id} {$product->title}: no verdict returned, leaving in queue.");
                $product->save();
                continue;
            }

            $ready = $verdict['ready_to_auto_publish'] ?? false;
            $confidence = $verdict['confidence'] ?? 'low';

            if (!$ready || $confidence !== 'high') {
                $this->line("  #{$product->id} {$product->title}: not ready ({$confidence}) — " . implode('; ', $verdict['reasons'] ?? []));
                $product->save();
                continue;
            }

            $liveInCategory = Product::storefrontVisible()->where('category', $product->category)->count();

            if ($liveInCategory >= $capPerCategory) {
                $this->line("  #{$product->id} {$product->title}: AI-ready but {$product->category->value} is at its live cap ({$liveInCategory}/{$capPerCategory}).");
                $capped[] = ['product' => $product, 'reasons' => $verdict['reasons'] ?? []];
                $product->save();
                continue;
            }

            $product->status = 'approved';
            $product->is_active = true;
            $product->approved_at = now();
            $product->published_at = now();
            $product->published_via = 'auto';
            $product->save();

            $this->info("  #{$product->id} {$product->title}: PUBLISHED automatically.");
            $published[] = ['product' => $product, 'reasons' => $verdict['reasons'] ?? []];
        }

        if (!empty($published)) {
            $adminEmail = config('farmtech.admin_notification_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new AutoPublishSummaryMailable($published, $capped));
            }
        }

        $this->info('Done. Published: ' . count($published) . ', held back by cap: ' . count($capped) . ', total checked: ' . $candidates->count() . '.');

        return 0;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Product> $candidates
     * @return array<int, array{ready_to_auto_publish: bool, confidence: string, reasons: array}> keyed by product id
     */
    private function runAiCheck($candidates): array
    {
        $payload = $candidates->map(fn (Product $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'category' => $p->category->value,
            'brand_name' => $p->brand_name,
            'model_number' => $p->model_number,
            'warranty_period' => $p->warranty_period,
            'specifications' => $p->specifications,
            'key_features' => $p->key_features,
            'included_items' => $p->included_items,
            'image_count' => $p->images()->count(),
            'retail_price_zar' => (float) $p->retail_price_zar,
            'landed_cost_zar' => (float) $p->landed_cost_zar,
        ])->values()->all();

        $workerPath = base_path('worker');
        $tmpFile = storage_path('app/private/auto-publish-' . Str::uuid() . '.json');
        File::ensureDirectoryExists(dirname($tmpFile));
        File::put($tmpFile, json_encode($payload, JSON_PRETTY_PRINT));

        try {
            $result = Process::path($workerPath)
                ->env($this->childProcessEnv($workerPath))
                ->timeout(max(120, $candidates->count() * 15))
                ->run(['node', 'src/autoPublishCheck.js', '--file=' . $tmpFile]);
        } finally {
            File::delete($tmpFile);
        }

        $output = $result->output() . $result->errorOutput();

        foreach (array_reverse(explode("\n", trim($output))) as $line) {
            if (str_starts_with($line, 'RESULT_JSON:')) {
                $decoded = json_decode(substr($line, strlen('RESULT_JSON:')), true);

                if (is_array($decoded)) {
                    $byId = [];
                    foreach ($decoded as $row) {
                        if (isset($row['id'])) {
                            $byId[(int) $row['id']] = $row;
                        }
                    }
                    return $byId;
                }
            }
        }

        $this->error("Auto-publish AI check produced no parseable result:\n{$output}");
        return [];
    }

    /** Mirrors SourcingPipelineRunner::childProcessEnv() — see that class for why this whitelist (not a full getenv() passthrough) is needed. */
    private function childProcessEnv(string $workerPath): array
    {
        $env = [];

        foreach (['SystemRoot', 'windir', 'ComSpec', 'PATH', 'Path'] as $key) {
            if ($value = getenv($key)) {
                $env[$key] = $value;
            }
        }

        $env['SystemRoot'] ??= 'C:\\Windows';
        $env['windir'] ??= 'C:\\Windows';
        $env['TEMP'] = getenv('TEMP') ?: 'C:\\Windows\\Temp';
        $env['TMP'] = getenv('TMP') ?: 'C:\\Windows\\Temp';

        $workerEnvFile = $workerPath . DIRECTORY_SEPARATOR . '.env';

        if (File::exists($workerEnvFile)) {
            $env = array_merge($env, Dotenv::parse(File::get($workerEnvFile)));
        }

        return $env;
    }
}
