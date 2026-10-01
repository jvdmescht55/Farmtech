<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Kuddebestuur home: pick a device section. Every section shares one herd book. */
class HerdHubController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('herd.hub', [
            'user' => $user,
            'modules' => collect(config('herd.modules'))->map(fn ($m, $key) => $m + ['key' => $key, 'unlocked' => $user->hasModule($key)]),
            'stats' => [
                'animals' => $user->animals()->where('in_herd', true)->where('status', 'active')->count(),
                'devices' => $user->readers()->count(),
                'alerts' => app(\App\Services\Herd\HerdAlerts::class)->forUser($user->id)->whereIn('severity', ['critical', 'warning'])->count(),
            ],
        ]);
    }
}
