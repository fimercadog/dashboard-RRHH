<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AttendanceController extends BaseCrudController
{
    protected string $model = Attendance::class;

    protected string $resource = AttendanceResource::class;

    protected array $with = ['employee.department', 'employee.position'];

    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];

    /**
     * Igual que BaseCrudController::store, pero idempotente cuando el cliente
     * envia un client_uuid (registros encolados en modo contingencia): un sync
     * reintentado no crea filas duplicadas.
     */
    public function store(Request $request, AuditService $audit)
    {
        if (! $request->filled('client_uuid')) {
            return parent::store($request, $audit);
        }

        $payload = $this->validatedInput($request);
        $payload['company_id'] ??= $this->companyId($request);
        $uuid = $payload['client_uuid'];

        $model = Attendance::firstOrCreate(['client_uuid' => $uuid], $payload);

        if ($model->wasRecentlyCreated) {
            $audit->record('created', $model, $request);
        }

        return (new AttendanceResource($model->load($this->with)))
            ->response()
            ->setStatusCode($model->wasRecentlyCreated ? 201 : 200);
    }
}
