<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLot extends Model
{
    protected $fillable = ['sale_catalogue_id', 'animal_id', 'lot_number', 'position', 'comment'];

    public function catalogue(): BelongsTo
    {
        return $this->belongsTo(SaleCatalogue::class, 'sale_catalogue_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
