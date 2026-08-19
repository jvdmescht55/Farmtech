<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use Dotenv\Dotenv;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Closes the "login and hit edit or post" loop: an admin fills in what they
 * saw on a supplier listing, this shells out to the real Node pipeline
 * (worker/src/pipeline.js — same code path as the CLI, same Gemini vetting,
 * same landed-cost math) with a cwd of worker/ so it picks up worker/.env
 * exactly like a manual run would, and lands the admin straight on the
 * review page for whatever got inserted (staged or rejected).
 */
class SourceController extends Controller
{
    public function create()
    {
        return view('admin.source.create', [
            'categories' => ProductCategory::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'raw_title' => ['required', 'string', 'max:255'],
            'category_hint' => ['required', Rule::enum(ProductCategory::class)],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_years' => ['nullable', 'integer', 'min:0'],
            'is_verified_supplier' => ['sometimes', 'boolean'],
            'has_trade_assurance' => ['sometimes', 'boolean'],
            'supplier_price_usd' => ['required', 'numeric', 'min:0'],
            'weight_kg' => ['required', 'numeric', 'min:0'],
            'duty_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'stock_status' => ['required', 'in:in_stock,pre_order'],
            'lead_time_days' => ['nullable', 'string', 'max:100'],
            'category_price_hint' => ['nullable', 'numeric', 'min:0'],
            'raw_specs_text' => ['required', 'string'],
            'image_urls' => ['nullable', 'string'],
        ]);

        $sku = 'FT-'.Str::upper(Str::random(4)).'-'.now()->format('mdHi');

        $listing = [
            'sku' => $sku,
            'raw_title' => $validated['raw_title'],
            'category_hint' => $validated['category_hint'],
            'supplier_name' => $validated['supplier_name'],
            'supplier_years' => $validated['supplier_years'] ?? null,
            'is_verified_supplier' => $request->boolean('is_verified_supplier'),
            'has_trade_assurance' => $request->boolean('has_trade_assurance'),
            'supplier_price_usd' => (float) $validated['supplier_price_usd'],
            'weight_kg' => (float) $validated['weight_kg'],
            'duty_rate' => (float) $validated['duty_rate'],
            'stock_status' => $validated['stock_status'],
            'lead_time_days' => $validated['lead_time_days'] ?: '7-12 business days',
            'category_price_hint' => isset($validated['category_price_hint']) ? (float) $validated['category_price_hint'] : null,
            'raw_specs_text' => $validated['raw_specs_text'],
            'image_urls' => array_values(array_filter(array_map('trim', explode("\n", $validated['image_urls'] ?? '')))),
        ];

        $workerPath = base_path('worker');
        $tmpFile = storage_path('app/private/sourcing-'.Str::uuid().'.json');
        File::ensureDirectoryExists(dirname($tmpFile));
        File::put($tmpFile, json_encode($listing, JSON_PRETTY_PRINT));

        $result = Process::path($workerPath)
            ->env($this->childProcessEnv($workerPath))
            ->timeout(180)
            ->run(['node', 'src/pipeline.js', '--file', $tmpFile, '--sku', $sku]);

        File::delete($tmpFile);

        $output = $result->output().$result->errorOutput();

        if (preg_match('/Inserted product #(\d+)/', $output, $matches)) {
            $productId = (int) $matches[1];
            $verdict = str_contains($output, 'status="rejected"') ? 'rejected — see the review page for why' : 'staged for review';

            return redirect()
                ->route('admin.products.show', $productId)
                ->with('status', "Sourced \"{$validated['raw_title']}\" — {$verdict}.");
        }

        return back()->withInput()->withErrors([
            'raw_title' => 'The sourcing pipeline did not report a successful insert. Raw output below.',
        ])->with('pipeline_output', $output);
    }

    /**
     * A minimal, explicit whitelist — NOT a full getenv() passthrough.
     *
     * Two Windows-only problems, one fix shape:
     *  1) PHP's built-in dev server (`php artisan serve`) handles each
     *     request in a child process that doesn't reliably propagate
     *     SystemRoot/windir/PATH to getenv(), and Node's crypto init on
     *     Windows needs SystemRoot to find bcrypt.dll — without it, node
     *     crashes before it even reaches our code ("Assertion failed:
     *     ncrypto::CSPRNG"). PATH is needed just to locate node.exe itself.
     *  2) Laravel's own root .env defines WORKER_DB_HOST=mysql (the Docker
     *     container's hostname — see .env.example). vlucas/phpdotenv has
     *     already putenv()'d that into THIS PHP process's environment. If we
     *     forwarded getenv() wholesale, that value would leak into the
     *     spawned node process, and since dotenv doesn't override variables
     *     that already exist, it would shadow worker/.env's real
     *     WORKER_DB_HOST=127.0.0.1 — pointing the pipeline at a Docker
     *     hostname that doesn't exist here. So: only forward what Windows
     *     process creation actually needs, nothing app-specific — and then
     *     explicitly re-parse worker/.env ourselves and layer it back on
     *     top, so its values are guaranteed to win no matter how Symfony
     *     Process merges explicit vs. inherited environment internally.
     *
     * Harmless no-op under Docker/Linux, where none of this applies.
     */
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

        $workerEnvFile = $workerPath.DIRECTORY_SEPARATOR.'.env';

        if (File::exists($workerEnvFile)) {
            $env = array_merge($env, Dotenv::parse(File::get($workerEnvFile)));
        }

        return $env;
    }
}
