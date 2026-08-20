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
     * `local_path` is just a filename (e.g. "FT-SKU-1.webp") — where it
     * actually lives depends on FILESYSTEM_DISK. Local disk: served from
     * public/uploads/products/. S3/R2: same relative key under a
     * "products/" prefix in the bucket, uploaded there directly by the
     * worker (see worker/src/lib/imagePipeline.js) instead of writing to
     * local disk at all when that's the active disk.
     */
    public function getUrlAttribute(): string
    {
        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->url('products/'.ltrim($this->local_path, '/'));
        }

        return asset('uploads/products/'.ltrim($this->local_path, '/'));
    }
}
