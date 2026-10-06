<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseCrudController
{
    protected string $model      = Payment::class;
    protected string $resource   = PaymentResource::class;
    protected array  $with       = ['cashAccount', 'user'];
    protected array  $searchable = ['reference', 'notes'];
    protected array  $filterable = ['payable_type', 'status', 'method', 'cash_account_id'];

    public function __construct(private PaymentService $paymentService) {}

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $service = $this->paymentService;
        $data = $this->validatedInput($request);

        try {
            $payment = $service->registerPayment(
                payableType:    $data['payable_type'],
                payableId:      (int) $data['payable_id'],
                cashAccountId:  (int) $data['cash_account_id'],
                amount:         (float) $data['amount'],
                method:         $data['method'],
                date:           $data['date'],
                companyId:      $this->companyId($request),
                userId:         $request->user()->id,
                reference:      $data['reference'] ?? null,
                notes:          $data['notes'] ?? null,
            );
        } catch (\LogicException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('payment_registered', $payment, $request);

        return (new PaymentResource($payment->load($this->with)))->response()->setStatusCode(201);
    }

    public function cancel(int $id, Request $request, PaymentService $service, AuditService $audit): JsonResponse
    {
        $payment = Payment::where('company_id', $this->companyId($request))->findOrFail($id);

        try {
            $service->cancelPayment($payment, $request->user()->id);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('payment_cancelled', $payment->fresh(), $request);

        return response()->json(['message' => 'Pago cancelado correctamente.']);
    }
}
