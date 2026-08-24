<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;

class AttendanceController extends BaseCrudController
{
    protected string $model = Attendance::class;
    protected string $resource = AttendanceResource::class;
    protected array $with = ['employee.department', 'employee.position'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];
}
