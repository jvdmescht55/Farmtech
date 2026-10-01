<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DevicePairing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Device pairing, so no key ever has to be typed into firmware:
 *
 *  1. Device:  POST /api/v1/pair  {"serial":"FT1-000123","model":"RFID Scanner V1","firmware":"1.0.0"}
 *              ← {"code":"482913","secret":"…","expires_in":900,"poll_every":5}
 *              Device shows "482 913" on its screen.
 *  2. Farmer:  Herd Manager → Toestelle → "Koppel met kode" → types 482913.
 *  3. Device:  POST /api/v1/pair/status {"secret":"…"} every few seconds
 *              ← {"status":"pending"} … then once: {"status":"paired","token":"…","device":"…","farm":"…"}
 *              Device stores the token in flash and uses it as its Bearer key from then on.
 */
class PairingController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'serial' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
            'firmware' => ['nullable', 'string', 'max:32'],
        ]);

        $secret = Str::random(40);
        $pairing = DevicePairing::create($data + [
            'code' => DevicePairing::freshCode(),
            'secret_hash' => hash('sha256', $secret),
            'expires_at' => now()->addMinutes(DevicePairing::TTL_MINUTES),
        ]);

        return response()->json([
            'code' => $pairing->code,
            'secret' => $secret,
            'expires_in' => DevicePairing::TTL_MINUTES * 60,
            'poll_every' => 5,
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        $secret = (string) ($request->input('secret') ?? $request->header('X-Pairing-Secret'));
        $pairing = $secret ? DevicePairing::with('reader.user')->where('secret_hash', hash('sha256', $secret))->first() : null;

        if (! $pairing) {
            return response()->json(['status' => 'unknown'], 404);
        }
        if (! $pairing->claimed_at) {
            return $pairing->expires_at->isPast()
                ? response()->json(['status' => 'expired'], 410)
                : response()->json(['status' => 'pending', 'expires_in' => (int) now()->diffInSeconds($pairing->expires_at)]);
        }
        if ($pairing->delivered_at || ! $pairing->token_encrypted) {
            return response()->json(['status' => 'delivered', 'error' => 'The key was already collected. Start pairing again if the device lost it.'], 410);
        }

        $token = $pairing->token_encrypted;
        $pairing->update(['token_encrypted' => null, 'delivered_at' => now()]);

        return response()->json([
            'status' => 'paired',
            'token' => $token,
            'device' => $pairing->reader->name,
            'farm' => $pairing->reader->user->farm_name ?: $pairing->reader->user->name,
        ]);
    }
}
