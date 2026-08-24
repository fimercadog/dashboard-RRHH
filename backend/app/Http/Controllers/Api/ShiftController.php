<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;

class ShiftController extends BaseCrudController
{
    protected string $model = Shift::class;
    protected string $resource = ShiftResource::class;
    protected array $searchable = ['name'];
    protected array $filterable = ['status' => 'status'];
}
