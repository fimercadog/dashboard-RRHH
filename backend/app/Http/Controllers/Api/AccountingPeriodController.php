<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountingPeriodResource;
use App\Models\AccountingPeriod;
use App\Services\AccountingService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AccountingPeriodController extends BaseCrudController
{
    protected string $model    = AccountingPeriod::class;
    protected string $resource = AccountingPeriodResource::class;
    protected array  $searchable = ['name'];
    protected array  $filterable = ['status'];

    public function __construct(private AccountingService $accounting) {}

    /** POST /accounting-periods — crea un período mediante AccountingService (valida solapamiento). */
    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $period = $this->accounting->openPeriod(
            $this->companyId($request),
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            $request->user()->id,
        );

        $audit->record('created', $period, $request);

        return (new AccountingPeriodResource($period))->response()->setStatusCode(201);
    }

    /** POST /accounting-periods/{id}/close — cierra el período. */
    public function close(Request $request, string $id, AuditService $audit): \Illuminate\Http\JsonResponse
    {
        $period = AccountingPeriod::where('company_id', $this->companyId($request))->findOrFail($id);

        $this->accounting->closePeriod($period, $request->user()->id);

        $audit->record('accounting_period_closed', $period, $request);

        return response()->json(['message' => 'Período cerrado correctamente.']);
    }
}
