<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reader extends Model
{
    protected $fillable = ['user_id', 'license_id', 'name', 'serial', 'model', 'firmware', 'battery_pct', 'api_token', 'last_synced_at', 'last_ip'];

    protected $hidden = ['api_token'];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    /** The plain key, only available right after it was issued (shown to the farmer once). */
    public ?string $plainToken = null;

    protected static function booted(): void
    {
        static::creating(function (Reader $reader) {
            if (! $reader->api_token) {
                $reader->plainToken = Str::random(48);
                $reader->api_token = self::hashToken($reader->plainToken);
            }
        });
    }

    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public static function findByToken(?string $plain): ?self
    {
        return $plain && strlen($plain) >= 20 ? static::with('user')->where('api_token', self::hashToken($plain))->first() : null;
    }

    /** Issue a new key; returns the plain value (store nowhere but on the device). */
    public function rotateToken(): string
    {
        $this->plainToken = Str::random(48);
        $this->update(['api_token' => self::hashToken($this->plainToken)]);

        return $this->plainToken;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(ReaderSync::class);
    }
}
