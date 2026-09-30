<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReaderSync extends Model
{
    protected $fillable = ['user_id', 'reader_id', 'source', 'filename', 'scan_count', 'matched_count', 'new_count'];

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }
}
