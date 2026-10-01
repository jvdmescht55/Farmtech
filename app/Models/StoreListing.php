<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Farmtech's own products (KraalTrac Pro, KraalTrac Watch…), written and published from admin. */
class StoreListing extends Model
{
    protected $fillable = ['slug', 'name', 'tagline', 'category', 'price_cents', 'availability', 'module', 'overview', 'features', 'specs', 'in_box', 'why', 'images', 'is_published', 'sort'];

    protected function casts(): array
    {
        return ['features' => 'array', 'specs' => 'array', 'in_box' => 'array', 'images' => 'array', 'is_published' => 'boolean'];
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true)->orderBy('sort')->orderBy('name');
    }

    public function priceLabel(): ?string
    {
        return $this->price_cents === null ? null : 'R'.number_format($this->price_cents / 100, 0, '.', ' ');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
