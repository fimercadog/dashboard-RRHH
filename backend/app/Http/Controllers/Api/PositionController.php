<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PositionResource;
use App\Models\Position;

class PositionController extends BaseCrudController
{
    protected string $model = Position::class;
    protected string $resource = PositionResource::class;
    protected array $with = ['department'];
    protected array $searchable = ['name', 'description'];
    protected array $filterable = ['status' => 'status', 'department_id' => 'department_id'];
}
