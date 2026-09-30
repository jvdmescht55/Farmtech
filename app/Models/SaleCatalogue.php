<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleCatalogue extends Model
{
    public const SECTIONS = ['Ewes / Ooie', 'Rams / Ramme', 'Ewe lambs / Ooilammers', 'Ram lambs / Ramlammers'];

    protected $fillable = ['user_id', 'title', 'breed', 'sale_date', 'venue', 'section', 'breeder_line'];

    protected function casts(): array
    {
        return ['sale_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(SaleLot::class)->orderBy('position');
    }

    /**
     * Logix-style lot numbering: lots share a number and are lettered within
     * it (66A, 66B, 66C, 66D, 67A ...). $perLot animals per lot number.
     */
    public function autoNumber(int $start, int $perLot): void
    {
        $perLot = max(1, min(26, $perLot));
        foreach ($this->lots()->get()->values() as $i => $lot) {
            $number = $start + intdiv($i, $perLot);
            $lot->update(['lot_number' => $perLot === 1 ? (string) $number : $number.chr(65 + $i % $perLot)]);
        }
    }
}
