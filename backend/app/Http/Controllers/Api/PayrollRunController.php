<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollRunResource;
use App\Models\PayrollPeriod;
use App\Services\AuditService;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    use ResolvesCompany;

    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    /**
     * Ejecuta (o re-ejecuta) el cálculo de nómina para un período.
     */
    public function run(Request $request, string $periodId, AuditService $audit): JsonResponse
    {
        $companyId = $this->companyId($request);

        $period = PayrollPeriod::where('id', $periodId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($period->status === 'closed') {
            return response()->json([
                'message' => 'El período de nómina está cerrado. Use nómina complementaria o solicite autorización de reversión.',
            ], 422);
        }

        $employeeId = $request->input('employee_id');

        $run = $this->payrollService->process($period, $employeeId ? (int) $employeeId : null);

        $audit->record('payroll_run_executed', $run, $request);

        return response()->json(new PayrollRunResource($run->load('items')), 201);
    }

    /**
     * Muestra un run con sus ítems.
     */
    public function show(Request $request, string $runId): JsonResponse
    {
        $companyId = $this->companyId($request);

        $run = \App\Models\PayrollRun::where('id', $runId)
            ->where('company_id', $companyId)
            ->with('items.employee', 'items.lines')
            ->firstOrFail();

        return response()->json(new PayrollRunResource($run));
    }

    /**
     * Aprueba un run completado.
     */
    public function approve(Request $request, string $runId, AuditService $audit): JsonResponse
    {
        $companyId = $this->companyId($request);

        $run = \App\Models\PayrollRun::where('id', $runId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($run->status === 'approved') {
            return response()->json(['message' => 'El run ya está aprobado.'], 422);
        }

        if ($run->status !== 'completed') {
            return response()->json(['message' => 'Solo se pueden aprobar runs completados.'], 422);
        }

        $run->items()->update(['status' => 'approved']);
        $run->forceFill(['status' => 'approved'])->save();

        $audit->record('payroll_run_approved', $run, $request);

        return response()->json(new PayrollRunResource($run->fresh()));
    }
}
