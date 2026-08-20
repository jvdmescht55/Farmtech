<?php

namespace App\Services;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Shells out to the real Node pipeline (worker/src/pipeline.js — same code
 * path as the CLI, same Gemini vetting, same landed-cost math) for one or
 * many listings at once. Used by both the admin "Source New Listing" form
 * (one listing) and the scraper webhook (a batch, one Node process for the
 * whole array rather than one per item).
 */
class SourcingPipelineRunner
{
    /** @param array<int, array<string, mixed>> $listings */
    public function run(array $listings, int $timeoutSeconds = 180): PipelineRunResult
    {
        $workerPath = base_path('worker');
        $tmpFile = storage_path('app/private/sourcing-'.Str::uuid().'.json');
        File::ensureDirectoryExists(dirname($tmpFile));
        File::put($tmpFile, json_encode(array_values($listings), JSON_PRETTY_PRINT));

        try {
            $result = Process::path($workerPath)
                ->env($this->childProcessEnv($workerPath))
                ->timeout($timeoutSeconds)
                ->run(['node', 'src/pipeline.js', '--file', $tmpFile]);
        } finally {
            File::delete($tmpFile);
        }

        $output = $result->output().$result->errorOutput();

        return $this->parseOutput($output, $result->successful());
    }

    private function parseOutput(string $output, bool $processSucceeded): PipelineRunResult
    {
        foreach (array_reverse(explode("\n", trim($output))) as $line) {
            if (str_starts_with($line, 'RESULT_JSON:')) {
                $decoded = json_decode(substr($line, strlen('RESULT_JSON:')), true);

                if (is_array($decoded)) {
                    return new PipelineRunResult(
                        items: $decoded['items'] ?? [],
                        counts: $decoded['counts'] ?? [],
                        rawOutput: $output,
                        parsed: true,
                    );
                }
            }
        }

        // The pipeline ran but never printed a parseable summary — surface
        // the raw output so whoever's watching (admin UI, job log) can see
        // why, rather than silently reporting "0 products processed".
        if (!$processSucceeded) {
            throw new RuntimeException("Sourcing pipeline exited with an error:\n{$output}");
        }

        return new PipelineRunResult(items: [], counts: [], rawOutput: $output, parsed: false);
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
