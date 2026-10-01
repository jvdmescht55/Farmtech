<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    public const WEIGH_TYPES = ['birth' => 'Birth', 'wean' => 'Wean', 'post_wean' => 'Post-wean', 'mature' => 'Mature', 'routine' => 'Routine'];

    protected $fillable = ['user_id', 'reader_sync_id', 'client_ref', 'animal_id', 'eid', 'visual_id', 'weight_kg', 'weigh_type', 'scanned_at'];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime', 'weight_kg' => 'decimal:1'];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function sync(): BelongsTo
    {
        return $this->belongsTo(ReaderSync::class, 'reader_sync_id');
    }
}
