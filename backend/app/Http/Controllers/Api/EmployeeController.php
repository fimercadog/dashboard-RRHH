<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;

class EmployeeController extends BaseCrudController
{
    protected string $model = Employee::class;
    protected string $resource = EmployeeResource::class;
    protected array $with = ['department', 'position', 'manager'];
    protected array $searchable = ['employee_code', 'first_name', 'last_name', 'identification_number', 'email'];
    protected array $filterable = ['status' => 'employment_status', 'department_id' => 'department_id', 'position_id' => 'position_id'];
}
