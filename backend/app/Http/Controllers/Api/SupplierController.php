<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;

class SupplierController extends BaseCrudController
{
    protected string $model    = Supplier::class;
    protected string $resource = SupplierResource::class;
    protected array $searchable  = ['name', 'nit', 'email'];
    protected array $filterable  = ['status' => 'status'];
}
