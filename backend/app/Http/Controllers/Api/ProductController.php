<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends BaseCrudController
{
    protected string $model    = Product::class;
    protected string $resource = ProductResource::class;
    protected array  $with     = ['category:id,name', 'brand:id,name', 'unit:id,name,abbreviation'];
    protected array  $searchable = ['sku', 'name'];
    protected array  $filterable = ['status', 'type', 'category_id', 'brand_id'];
}
