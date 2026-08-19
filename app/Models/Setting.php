<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'label'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("settings.{$key}", 300, function () use ($key, $default) {
            $row = static::where('key', $key)->first();

            if (! $row) {
                return $default;
            }

            return match ($row->type) {
                'integer' => (int) $row->value,
                'decimal' => (float) $row->value,
                'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($row->value, true),
                default => $row->value,
            };
        });
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $label = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $type === 'json' ? json_encode($value) : $value,
                'type' => $type,
                'label' => $label,
            ]
        );

        Cache::forget("settings.{$key}");
    }
}
