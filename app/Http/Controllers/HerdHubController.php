<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Herd Management home: every software module, open if the account holds its licence. */
class HerdHubController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $modules = collect(config('herd.modules'))->map(fn ($m, $key) => $m + [
            'key' => $key,
            'unlocked' => $user->hasModule($key),
        ]);

        return view('herd.hub', [
            'user' => $user,
            'modules' => $modules,
            'stats' => [
                'animals' => $user->animals()->where('in_herd', true)->where('status', 'active')->count(),
                'readers' => $user->readers()->count(),
                'lastSync' => $user->readers()->max('last_synced_at'),
            ],
        ]);
    }
}
