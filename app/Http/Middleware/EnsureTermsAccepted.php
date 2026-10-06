<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Farmers who accepted an older version of the terms are asked to accept the
 * current one (config('legal.terms_version')) before using Herd Manager.
 */
class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();
        if ($u && $u->role === 'customer' && $request->is('app', 'app/*', 'pair', 'pair/*', 'activate')
            && (! $u->terms_accepted_at || $u->terms_accepted_at->lt(\Carbon\Carbon::parse(config('legal.terms_version'))))) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Please accept the updated terms first.'], 409);
            }

            return redirect()->guest(route('terms.accept'));
        }

        return $next($request);
    }
}
