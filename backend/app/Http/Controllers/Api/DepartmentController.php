<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;

class DepartmentController extends BaseCrudController
{
    protected string $model = Department::class;
    protected string $resource = DepartmentResource::class;
    protected array $with = ['manager'];
    protected array $searchable = ['name', 'description'];
    protected array $filterable = ['status' => 'status'];
}
