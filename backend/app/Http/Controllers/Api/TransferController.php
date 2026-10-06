<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Services\AuditService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends BaseCrudController
{
    protected string $model      = Transfer::class;
    protected string $resource   = TransferResource::class;
    protected array  $with       = ['fromAccount', 'toAccount', 'user'];
    protected array  $searchable = ['reference', 'notes'];
    protected array  $filterable = ['status', 'from_account_id', 'to_account_id'];

    public function __construct(private PaymentService $paymentService) {}

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $service = $this->paymentService;
        $data = $this->validatedInput($request);

        try {
            $transfer = $service->transfer(
                fromAccountId: (int) $data['from_account_id'],
                toAccountId:   (int) $data['to_account_id'],
                amount:        (float) $data['amount'],
                date:          $data['date'],
                companyId:     $this->companyId($request),
                userId:        $request->user()->id,
                reference:     $data['reference'] ?? null,
                notes:         $data['notes'] ?? null,
            );
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('transfer_registered', $transfer, $request);

        return (new TransferResource($transfer->load($this->with)))->response()->setStatusCode(201);
    }

    public function cancel(int $id, Request $request, PaymentService $service, AuditService $audit): JsonResponse
    {
        $transfer = Transfer::where('company_id', $this->companyId($request))->findOrFail($id);

        try {
            $service->cancelTransfer($transfer, $request->user()->id);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('transfer_cancelled', $transfer->fresh(), $request);

        return response()->json(['message' => 'Transferencia cancelada correctamente.']);
    }
}
