<?php

namespace App\Http\Middleware;

use App\Services\Billing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Herd Manager after the free months: once access has run out (and the grace
 * days are over) the farm is read-only. Looking, searching, printing and
 * downloading keep working; only adding or changing records waits for a
 * payment. Device syncs (the API) are never blocked, so no weighing is lost.
 */
class EnsureSubscription
{
    public function __construct(private Billing $billing) {}

    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();
        if (! $u || $u->role !== 'customer' || ! $request->is('app', 'app/*')) {
            return $next($request);
        }

        $status = $this->billing->status($u);
        view()->share('billing', $status);

        // Reading never stops. A USB sync from the scale is saved too: those weighings exist already.
        if ($status['read_only'] && ! $request->isMethodSafe() && ! $request->routeIs('rfid.data.usb')) {
            $message = 'Your free months are over, so Herd Manager is read-only. Your records are safe. Pay for a month to keep adding to them.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 402);
            }

            return redirect()->route('billing.show')->with('status', $message);
        }

        return $next($request);
    }
}
