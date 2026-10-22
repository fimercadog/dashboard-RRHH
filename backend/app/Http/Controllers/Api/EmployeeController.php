<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends BaseCrudController
{
    protected string $model = Employee::class;
    protected string $resource = EmployeeResource::class;
    protected array $with = ['department', 'position', 'manager'];
    protected array $searchable = ['employee_code', 'first_name', 'last_name', 'identification_number', 'email'];
    protected array $filterable = ['status' => 'employment_status', 'department_id' => 'department_id', 'position_id' => 'position_id'];

    /** Carga work_history y documents solo en el detalle, no en el listado. */
    public function show(Request $request, string $id): JsonResource
    {
        $model = Employee::query()
            ->where('company_id', $this->companyId($request))
            ->with([...$this->with, 'workHistory', 'documents'])
            ->findOrFail($id);

        return new EmployeeResource($model);
    }

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $som = now()->startOfMonth();

        $base = Employee::where('company_id', $cid);

        return response()->json([
            'total_active'      => (clone $base)->where('employment_status', 'active')->count(),
            'hired_this_month'  => (clone $base)->whereDate('hire_date', '>=', $som)->count(),
            'by_contract_type'  => (clone $base)->where('employment_status', 'active')
                ->select('contract_type', DB::raw('count(*) as total'))
                ->groupBy('contract_type')
                ->pluck('total', 'contract_type'),
        ]);
    }

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

    public function uploadAvatar(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $employee = Employee::query()->where('company_id', $this->companyId($request))->findOrFail($id);

        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
        }

        $path = $request->file('avatar')->store("employee-avatars/{$employee->company_id}", 'public');
        $employee->update(['avatar' => $path]);

        return response()->json(['avatar_url' => Storage::disk('public')->url($path)]);
    }

    public function deleteAvatar(Request $request, string $id): JsonResponse
    {
        $employee = Employee::query()->where('company_id', $this->companyId($request))->findOrFail($id);

        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
            $employee->update(['avatar' => null]);
        }

        return response()->json(['avatar_url' => null]);
    }
}
