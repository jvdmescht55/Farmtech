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

    /**
     * local_path is expected to be a relative path under the public disk
     * (e.g. "products/p_123_1.jpg"), normalized by the import pipeline. But
     * an import that skips or fails the local-download step can leave it
     * empty, or — from a differently-shaped feed — pointing straight at an
     * already-absolute CDN URL (Alibaba/S3/etc.). Both cases are handled
     * explicitly here so neither ever gets run through asset()/Storage::url()
     * a second time, which would double-prepend the app/CDN host onto an
     * already-complete URL and produce a broken src.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->local_path && preg_match('#^https?://#i', $this->local_path)) {
            return $this->local_path;
        }

        if ($this->local_path) {
            return config('filesystems.default') === 's3'
                ? Storage::disk('s3')->url(ltrim($this->local_path, '/'))
                : asset('storage/' . ltrim($this->local_path, '/'));
        }

        // No usable local file — fall back to the original supplier URL
        // (still a real photo of the product) rather than a broken link.
        return $this->original_url ?: null;
    }
}
