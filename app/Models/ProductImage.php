<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id', 'original_url', 'local_path', 'is_thumbnail', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_thumbnail' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->url(ltrim($this->local_path, '/'));
        }

        return asset('storage/' . ltrim($this->local_path, '/'));
    }
}
