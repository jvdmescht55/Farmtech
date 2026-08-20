<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('admin.login');
        }

        // Gates the panel — both roles pass. Per-section access (Settings,
        // Users, Sourcing, Products) is enforced separately via the
        // manage-catalog/manage-settings/manage-users Gates registered in
        // AppServiceProvider, applied to those specific route groups.
        if (! $request->user()->canAccessAdminPanel()) {
            abort(403);
        }

        return $next($request);
    }
}
