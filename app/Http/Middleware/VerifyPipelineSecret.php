<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards POST /api/pipeline/webhook. Fails closed: if no secret is
 * configured server-side, the endpoint is disabled (503) rather than
 * silently accepting unauthenticated requests.
 */
class VerifyPipelineSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('services.pipeline_webhook.secret');

        if (!$configured) {
            return response()->json([
                'message' => 'Pipeline webhook is not configured. Set PIPELINE_WEBHOOK_SECRET to enable it.',
            ], 503);
        }

        $provided = (string) $request->header('X-Pipeline-Secret', '');

        if (!hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Invalid or missing X-Pipeline-Secret header.'], 401);
        }

        return $next($request);
    }
}
