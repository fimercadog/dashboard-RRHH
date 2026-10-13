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

    /** Selector ligero con búsqueda server-side — accesible a cualquier rol autenticado de la empresa. */
    public function selector(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $search    = $request->string('search')->trim()->value();
        $limit     = min((int) $request->input('limit', 30), 50);
        $id        = $request->input('id');

        $query = Employee::where('company_id', $companyId);

        if ($id !== null) {
            // Modo edición: resolver label por ID sin filtrar por employment_status
            $employee = $query->where('id', (int) $id)->first(['id', 'first_name', 'last_name']);
            if (! $employee) {
                return response()->json([]);
            }

            return response()->json([[
                'id'    => $employee->id,
                'label' => $employee->first_name.' '.$employee->last_name,
            ]]);
        }

        // Modo búsqueda normal: solo activos
        $query->where('employment_status', 'active');

        if (mb_strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', '%'.$search.'%')
                  ->orWhere('last_name', 'LIKE', '%'.$search.'%');
            });
        }

        $employees = $query->orderBy('first_name')->limit($limit)->get(['id', 'first_name', 'last_name']);

        return response()->json($employees->map(fn ($e) => [
            'id'    => $e->id,
            'label' => $e->first_name.' '.$e->last_name,
        ]));
    }
}
