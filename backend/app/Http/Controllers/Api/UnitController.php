<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UnitResource;
use App\Models\Unit;

class UnitController extends BaseCrudController
{
    protected string $model    = Unit::class;
    protected string $resource = UnitResource::class;
    protected array  $searchable = ['name', 'abbreviation'];
    protected array  $filterable = ['status'];
}
