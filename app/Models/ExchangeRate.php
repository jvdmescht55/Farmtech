<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    public $timestamps = false;

    protected $fillable = ['currency_pair', 'rate', 'updated_at'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'updated_at' => 'datetime',
        ];
    }

    public static function latestRate(string $pair = 'USDZAR'): ?float
    {
        $row = static::where('currency_pair', $pair)->first();

        return $row ? (float) $row->rate : null;
    }

    public static function store(string $pair, float $rate): void
    {
        static::updateOrCreate(
            ['currency_pair' => $pair],
            ['rate' => $rate, 'updated_at' => now()]
        );
    }
}
