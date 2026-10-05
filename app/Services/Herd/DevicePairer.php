<?php

namespace App\Services\Herd;

use App\Models\DevicePairing;
use App\Models\Reader;
use App\Models\User;

/** Claims a device's 6-digit pairing code for a farmer (used by Devices and farmtech.site/pair). */
class DevicePairer
{
    /** @return array{0: ?Reader, 1: ?string} [reader, error] */
    public function claim(User $user, string $code, ?string $name = null, ?string $kind = null, ?string $location = null): array
    {
        $code = preg_replace('/\D/', '', $code);
        $pairing = DevicePairing::open()->where('code', $code)->first();
        if (! $pairing) {
            return [null, 'That code isn\'t valid or has expired. Switch the device off and on for a new one.'];
        }

        $reader = $pairing->serial ? $user->readers()->where('serial', $pairing->serial)->first() : null;
        if ($reader) {
            $plain = $reader->rotateToken();
            $reader->update(array_filter(['model' => $pairing->model, 'firmware' => $pairing->firmware]));
        } else {
            $kind ??= str_contains(strtolower((string) $pairing->model), 'watch') ? 'watch' : 'handheld';
            $reader = $user->readers()->create([
                'kind' => $kind,
                'location' => $location,
                'name' => $name ?: ($pairing->model ?: ($kind === 'watch' ? 'KraalTrac Watch' : 'KraalTrac Pro')).($pairing->serial ? ' · '.$pairing->serial : ''),
                'serial' => $pairing->serial,
                'model' => $pairing->model,
                'firmware' => $pairing->firmware,
            ]);
            $plain = $reader->plainToken;
        }
        $pairing->update(['reader_id' => $reader->id, 'token_encrypted' => $plain, 'claimed_at' => now()]);

        return [$reader, null];
    }
}
