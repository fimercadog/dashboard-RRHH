<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollPeriodController extends BaseCrudController
{
    protected string $model    = PayrollPeriod::class;
    protected string $resource = PayrollPeriodResource::class;
    protected array  $with     = [];
    protected array  $searchable  = ['name'];
    protected array  $filterable  = ['status' => 'status'];
    protected array  $immutableOnUpdate = [
        'company_id', 'status', 'authorized_reversal',
        'reversal_authorized_by', 'reversal_authorized_at', 'closed_by', 'closed_at',
    ];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = PayrollPeriod::where('company_id', $cid);
        return response()->json([
            'total'    => $q->clone()->count(),
            'draft'    => $q->clone()->where('status', 'draft')->count(),
            'running'  => $q->clone()->where('status', 'running')->count(),
            'closed'   => $q->clone()->where('status', 'closed')->count(),
        ]);
    }

    /**
     * Cierra el período — CA-N: verifica parámetros antes de cerrar.
     */
    public function close(Request $request, string $id, AuditService $audit): JsonResponse
    {
        $companyId = $this->companyId($request);

        $period = PayrollPeriod::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($period->status === 'closed') {
            return response()->json(['message' => 'El período ya está cerrado.'], 422);
        }

        $missing = $period->canClose();

        if (! empty($missing)) {
            $audit->record('payroll_period_close_blocked', $period, $request, ['missing' => $missing]);
            return response()->json([
                'message'       => 'No se puede cerrar el período: faltan parámetros obligatorios.',
                'missing_params' => $missing,
            ], 422);
        }

        $period->update([
            'status'    => 'closed',
            'closed_by' => $request->user()?->id,
            'closed_at' => now(),
        ]);

        $audit->record('payroll_period_closed', $period, $request);

        return response()->json(new PayrollPeriodResource($period->fresh()));
    }

    /**
     * Crea un período complementario vinculado al período cerrado.
     */
    public function complementary(Request $request, string $id, AuditService $audit): JsonResponse
    {
        $companyId = $this->companyId($request);

        $original = PayrollPeriod::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'payment_date' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        $period = PayrollPeriod::create([
            ...$data,
            'company_id'         => $companyId,
            'period_type'        => $original->period_type,
            'status'             => 'draft',
            'is_complementary'   => true,
            'original_period_id' => $original->id,
        ]);

        $audit->record('payroll_period_complementary_created', $period, $request);

        return response()->json(new PayrollPeriodResource($period), 201);
    }
}
