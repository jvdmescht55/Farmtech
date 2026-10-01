<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceReading extends Model
{
    protected $fillable = ['reader_id', 'metric', 'value', 'recorded_at'];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'value' => 'float'];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }
}
