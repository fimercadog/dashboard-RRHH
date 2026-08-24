<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VacationRequestResource;
use App\Models\VacationRequest;

class VacationRequestController extends BaseCrudController
{
    protected string $model = VacationRequest::class;
    protected string $resource = VacationRequestResource::class;
    protected array $with = ['employee', 'approver'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];
}
