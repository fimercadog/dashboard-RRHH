<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(Product $product, float $available, float $requested)
    {
        parent::__construct(
            "Stock insuficiente para '{$product->name}': disponible {$available}, solicitado {$requested}."
        );
    }
}
