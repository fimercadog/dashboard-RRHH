<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionRequestResource;
use App\Models\PermissionRequest;

class PermissionRequestController extends BaseCrudController
{
    protected string $model = PermissionRequest::class;
    protected string $resource = PermissionRequestResource::class;
    protected array $with = ['employee', 'approver'];
    protected array $filterable = ['status' => 'status', 'type' => 'type', 'employee_id' => 'employee_id'];
}
