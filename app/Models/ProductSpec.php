<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSpec extends Model
{
    protected $fillable = [
        'product_id', 'spec_group', 'spec_key', 'spec_value', 'is_highlight',
    ];

    protected function casts(): array
    {
        return [
            'is_highlight' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
