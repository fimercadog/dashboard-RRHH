<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeWorkHistoryResource;
use App\Models\Employee;
use App\Models\EmployeeWorkHistory;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class EmployeeWorkHistoryController extends Controller
{
    use ResolvesCompany;

    /** Override: filtra por employee además de company. */
    public function index(Request $request, string $employee): ResourceCollection
    {
        $companyId = $this->companyId($request);
        // Validar que el employee pertenece a la empresa
        Employee::where('company_id', $companyId)->findOrFail($employee);

        $items = EmployeeWorkHistory::where('company_id', $companyId)
            ->where('employee_id', $employee)
            ->orderByDesc('effective_date')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return EmployeeWorkHistoryResource::collection($items);
    }

    /** Override: inyecta employee_id desde la URL. */
    public function store(Request $request, AuditService $audit, string $employee): JsonResponse
    {
        $companyId = $this->companyId($request);
        $emp = Employee::where('company_id', $companyId)->findOrFail($employee);

        $payload = $request->validate([
            'event_type'     => ['required', 'in:hire,promotion,salary_adjustment,recognition,other'],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $payload['company_id']  = $companyId;
        $payload['employee_id'] = $emp->id;

        $model = EmployeeWorkHistory::create($payload);
        $audit->record('created', $model, $request);

        return (new EmployeeWorkHistoryResource($model))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $employee, string $id, AuditService $audit): JsonResource
    {
        $companyId = $this->companyId($request);
        Employee::where('company_id', $companyId)->findOrFail($employee);

        $model = EmployeeWorkHistory::where('company_id', $companyId)
            ->where('employee_id', $employee)
            ->findOrFail($id);

        $oldValues = $model->getOriginal();
        $payload = $request->validate([
            'event_type'     => ['sometimes', 'in:hire,promotion,salary_adjustment,recognition,other'],
            'title'          => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'effective_date' => ['sometimes', 'date'],
        ]);

        $model->update($payload);
        $audit->record('updated', $model, $request, $oldValues);

        return new EmployeeWorkHistoryResource($model);
    }

    public function destroy(Request $request, string $employee, string $id, AuditService $audit): Response
    {
        $companyId = $this->companyId($request);
        Employee::where('company_id', $companyId)->findOrFail($employee);

        $model = EmployeeWorkHistory::where('company_id', $companyId)
            ->where('employee_id', $employee)
            ->findOrFail($id);

        $audit->record('deleted', $model, $request);
        $model->delete();

        return response()->noContent();
    }
}
