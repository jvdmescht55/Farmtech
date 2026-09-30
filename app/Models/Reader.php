<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reader extends Model
{
    protected $fillable = ['user_id', 'license_id', 'name', 'serial', 'model', 'api_token', 'last_synced_at'];

    protected $hidden = ['api_token'];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Reader $reader) {
            $reader->api_token ??= Str::random(48);
        });
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
