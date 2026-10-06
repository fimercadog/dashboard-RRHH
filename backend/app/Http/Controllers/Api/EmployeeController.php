<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends BaseCrudController
{
    protected string $model = Employee::class;
    protected string $resource = EmployeeResource::class;
    protected array $with = ['department', 'position', 'manager'];
    protected array $searchable = ['employee_code', 'first_name', 'last_name', 'identification_number', 'email'];
    protected array $filterable = ['status' => 'employment_status', 'department_id' => 'department_id', 'position_id' => 'position_id'];

    /** Selector ligero — accesible a cualquier rol autenticado de la empresa. */
    public function selector(Request $request): JsonResponse
    {
        $employees = Employee::where('company_id', $this->companyId($request))
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($employees->map(fn ($e) => [
            'id'    => $e->id,
            'label' => "{$e->first_name} {$e->last_name}",
        ]));
    }
}
