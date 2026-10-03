<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BrandResource;
use App\Models\Brand;

class BrandController extends BaseCrudController
{
    protected string $model    = Brand::class;
    protected string $resource = BrandResource::class;
    protected array  $searchable = ['name'];
    protected array  $filterable = ['status'];
}
