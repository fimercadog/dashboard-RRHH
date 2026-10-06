<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InvalidProductTypeException extends RuntimeException
{
    public function __construct(Product $product)
    {
        parent::__construct(
            "El producto '{$product->name}' es de tipo 'service' y no tiene inventario físico."
        );
    }
}
