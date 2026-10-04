<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use App\Services\AuditService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends BaseCrudController
{
    protected string $model    = PurchaseInvoice::class;
    protected string $resource = PurchaseInvoiceResource::class;
    protected array $with       = ['supplier:id,name', 'accountPayable'];
    protected array $searchable = ['number', 'notes'];
    protected array $filterable = ['status' => 'status', 'supplier_id' => 'supplier_id'];

    public function store(Request $request, AuditService $audit)
    {
        $companyId = $this->companyId($request);
        $payload   = $this->validatedInput($request);
        $payload['company_id'] = $companyId;
        $payload['user_id']    = $request->user()->id;
        $payload['status']     ??= 'draft';

        $invoice = PurchaseInvoice::create($payload)->load($this->with);
        $audit->record('created', $invoice, $request);

        return (new PurchaseInvoiceResource($invoice))->response()->setStatusCode(201);
    }

    /** POST /purchase-invoices/{id}/post — crea CxP */
    public function post(Request $request, string $id, PurchaseService $purchases, AuditService $audit)
    {
        $invoice = PurchaseInvoice::where('company_id', $this->companyId($request))->findOrFail($id);

        try {
            $purchases->postInvoice($invoice, $request->user()->id);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('posted', $invoice->fresh(), $request);

        return new PurchaseInvoiceResource($invoice->fresh()->load($this->with));
    }
}
