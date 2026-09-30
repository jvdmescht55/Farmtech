<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class License extends Model
{
    public const MODULES = ['rfid' => 'RFID Herd Manager'];

    protected $fillable = ['code', 'module', 'device_serial', 'device_model', 'user_id', 'activated_at', 'revoked_at', 'notes'];

    protected function casts(): array
    {
        return ['activated_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Format: FT-XXXX-XXXX-XXXX, no ambiguous characters (0/O, 1/I). */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $parts = [];
            for ($i = 0; $i < 3; $i++) {
                $parts[] = collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
            }
            $code = 'FT-'.implode('-', $parts);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }

    public function isAvailable(): bool
    {
        return $this->user_id === null && $this->revoked_at === null;
    }

    public function isActive(): bool
    {
        return $this->user_id !== null && $this->revoked_at === null;
    }

    public function moduleLabel(): string
    {
        return self::MODULES[$this->module] ?? $this->module;
    }
}
