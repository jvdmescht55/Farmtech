<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly Product $product)
    {
        parent::__construct("Insufficient stock for \"{$product->title}\" — someone else may have just bought the last unit.");
    }
}
