<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PayrollParameterResource;
use App\Models\PayrollParameter;
use App\Models\PayrollParameterValue;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollParameterController extends BaseCrudController
{
    protected string $model    = PayrollParameter::class;
    protected string $resource = PayrollParameterResource::class;
    protected array  $with     = [];
    protected array  $searchable = ['code', 'name'];
    protected array  $filterable = ['scope' => 'scope'];
    protected array  $immutableOnUpdate = ['code', 'scope'];

    /**
     * Agrega un nuevo valor vigente al parámetro (inmutabilidad: no edita el existente).
     * Cierra el valor anterior si existe.
     */
    public function addValue(Request $request, string $id, AuditService $audit): JsonResponse
    {
        $companyId = $this->companyId($request);

        $param = PayrollParameter::where('id', $id)
            ->where(fn ($q) =>
                $q->whereNull('company_id')->orWhere('company_id', $companyId)
            )->firstOrFail();

        $data = $request->validate([
            'effective_from' => 'required|date',
            'value'          => 'required|numeric',
            'unit'           => 'required|string|max:30',
            'source_norm'    => 'required|string|max:300',
            'source_url'     => 'nullable|url|max:500',
            'verified_at'    => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $newFrom = Carbon::parse($data['effective_from']);

        // Cerrar el valor anterior
        $prev = PayrollParameterValue::where('parameter_id', $param->id)
            ->whereNull('effective_to')
            ->where('effective_from', '<', $newFrom->format('Y-m-d'))
            ->orderByDesc('effective_from')
            ->first();

        if ($prev) {
            // Usar update() directo en el query builder para evitar el guard del modelo
            PayrollParameterValue::where('id', $prev->id)
                ->update(['effective_to' => $newFrom->subDay()->format('Y-m-d')]);
        }

        $value = PayrollParameterValue::create([
            ...$data,
            'parameter_id' => $param->id,
            'created_by'   => $request->user()?->id,
            'verified_by'  => $data['verified_at'] ? $request->user()?->id : null,
        ]);

        $audit->record('payroll_parameter_value_added', $param, $request);

        return response()->json($value, 201);
    }

    /** Historial de valores del parámetro. */
    public function history(Request $request, string $id): JsonResponse
    {
        $companyId = $this->companyId($request);

        $param = PayrollParameter::where('id', $id)
            ->where(fn ($q) =>
                $q->whereNull('company_id')->orWhere('company_id', $companyId)
            )->firstOrFail();

        $values = PayrollParameterValue::where('parameter_id', $param->id)
            ->orderByDesc('effective_from')
            ->get();

        return response()->json($values);
    }
}
