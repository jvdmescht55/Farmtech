<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevicePairing extends Model
{
    public const TTL_MINUTES = 15;

    protected $fillable = ['code', 'secret_hash', 'serial', 'model', 'firmware', 'reader_id', 'token_encrypted', 'claimed_at', 'delivered_at', 'expires_at'];

    protected $hidden = ['token_encrypted', 'secret_hash'];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime', 'delivered_at' => 'datetime', 'expires_at' => 'datetime', 'token_encrypted' => 'encrypted'];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function scopeOpen($q)
    {
        return $q->whereNull('claimed_at')->where('expires_at', '>', now());
    }

    /** A 6-digit code no other open pairing is using. */
    public static function freshCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::open()->where('code', $code)->exists());

        return $code;
    }
}
