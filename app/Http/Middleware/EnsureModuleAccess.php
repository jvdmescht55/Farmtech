<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Gates a customer software module (route middleware `module:rfid`) behind an active device licence. */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasModule($module)) {
            return redirect()->route('account.activate')->with('status', 'Type in the activation code from your device\'s box to unlock this section.');
        }

        return $next($request);
    }
}
