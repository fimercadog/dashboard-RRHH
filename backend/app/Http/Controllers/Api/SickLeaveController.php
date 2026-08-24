<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SickLeaveResource;
use App\Models\SickLeave;

class SickLeaveController extends BaseCrudController
{
    protected string $model = SickLeave::class;
    protected string $resource = SickLeaveResource::class;
    protected array $with = ['employee'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];
}
