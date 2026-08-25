<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'source_review_id', 'author_name', 'rating',
        'review_text', 'original_review_text', 'text_cleaned',
        'verified_purchase', 'review_date', 'source',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'verified_purchase' => 'boolean',
            'text_cleaned' => 'boolean',
            'review_date' => 'date',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
