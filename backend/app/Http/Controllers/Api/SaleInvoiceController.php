<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleInvoiceResource;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Services\AuditService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleInvoiceController extends BaseCrudController
{
    protected string $model    = SaleInvoice::class;
    protected string $resource = SaleInvoiceResource::class;
    protected array $with       = ['client:id,company_name,first_name,last_name', 'saleOrder:id,number,warehouse_id', 'items.product:id,sku,name', 'accountReceivable'];
    protected array $searchable = ['number', 'notes'];
    protected array $filterable = ['status' => 'status', 'type' => 'type', 'client_id' => 'client_id'];

    public function store(Request $request, AuditService $audit)
    {
        $companyId = $this->companyId($request);
        $payload   = $this->validatedInput($request);

        $invoice = DB::transaction(function () use ($payload, $companyId, $request) {
            $payload['company_id'] = $companyId;
            $payload['user_id']    = $request->user()->id;
            $payload['status']     ??= 'draft';
            $payload['type']       ??= 'invoice';

            $items = $payload['items'] ?? [];
            unset($payload['items']);

            [$subtotal, $discountTotal] = $this->calcTotals($items);
            $payload['subtotal']       = $subtotal;
            $payload['discount_total'] = $discountTotal;
            $payload['tax']            ??= 0;
            $payload['total']          = $subtotal - $discountTotal + ($payload['tax'] ?? 0);

            $invoice = SaleInvoice::create($payload);

            foreach ($items as $item) {
                $disc = ($item['discount_pct'] ?? 0) / 100;
                SaleInvoiceItem::create([
                    'sale_invoice_id' => $invoice->id,
                    'product_id'      => $item['product_id'],
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'discount_pct'    => $item['discount_pct'] ?? 0,
                    'subtotal'        => $item['quantity'] * $item['unit_price'] * (1 - $disc),
                    'cost_at_time'    => 0, // se llena al postear
                ]);
            }

            return $invoice->load($this->with);
        });

        $audit->record('created', $invoice, $request);

        return (new SaleInvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function post(int $id, Request $request, SaleService $service, AuditService $audit)
    {
        $invoice = SaleInvoice::where('company_id', $this->companyId($request))->findOrFail($id);

        try {
            $service->postInvoice($invoice, $request->user()->id);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('posted', $invoice->fresh(), $request);

        return new SaleInvoiceResource($invoice->fresh($this->with));
    }

    public function createReturn(int $id, Request $request, SaleService $service, AuditService $audit)
    {
        $invoice = SaleInvoice::where('company_id', $this->companyId($request))->findOrFail($id);

        try {
            $return = $service->createReturn($invoice, $request->user()->id);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('return_created', $return, $request);

        return (new SaleInvoiceResource($return->load($this->with)))->response()->setStatusCode(201);
    }

    private function calcTotals(array $items): array
    {
        $subtotal = $discountTotal = 0;
        foreach ($items as $item) {
            $lineGross = $item['quantity'] * $item['unit_price'];
            $disc      = $lineGross * (($item['discount_pct'] ?? 0) / 100);
            $subtotal      += $lineGross;
            $discountTotal += $disc;
        }
        return [$subtotal, $discountTotal];
    }
}
