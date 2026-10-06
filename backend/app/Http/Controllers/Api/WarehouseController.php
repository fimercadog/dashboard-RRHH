<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;

class WarehouseController extends BaseCrudController
{
    protected string $model    = Warehouse::class;
    protected string $resource = WarehouseResource::class;
    protected array  $searchable = ['name', 'location'];
    protected array  $filterable = ['status'];
}
