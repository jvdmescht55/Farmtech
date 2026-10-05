<?php

namespace App\Http\Controllers;

use App\Models\DevicePairing;
use App\Services\Herd\DevicePairer;
use Illuminate\Http\Request;

/** farmtech.site/pair — the short address the device's screen tells you to open. */
class PairController extends Controller
{
    public function show(Request $request)
    {
        return view('herd.pair', ['code' => preg_replace('/\D/', '', (string) $request->query('code'))]);
    }

    public function claim(Request $request, DevicePairer $pairer)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12'], 'name' => ['nullable', 'string', 'max:120']]);
        [$reader, $error] = $pairer->claim($request->user(), $data['code'], $data['name'] ?? null);
        if ($error) {
            return response()->json(['ok' => false, 'error' => $error], 422);
        }
        session()->push('claimed_pairings', preg_replace('/\D/', '', $data['code']));

        return response()->json(['ok' => true, 'device' => $reader->name, 'kind' => $reader->kind]);
    }

    /** Has the device collected its key yet? Polled by the pair page. */
    public function check(string $code)
    {
        abort_unless(in_array($code, session('claimed_pairings', []), true), 404);
        $p = DevicePairing::where('code', $code)->whereNotNull('claimed_at')->latest()->first();

        return response()->json(['delivered' => (bool) $p?->delivered_at]);
    }
}
