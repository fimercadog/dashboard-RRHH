<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PurchaseReceiptResource;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Services\AuditService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReceiptController extends BaseCrudController
{
    protected string $model    = PurchaseReceipt::class;
    protected string $resource = PurchaseReceiptResource::class;
    protected array $with       = ['supplier:id,name', 'warehouse:id,name', 'items.product:id,sku,name'];
    protected array $searchable = ['number', 'notes'];
    protected array $filterable = ['status' => 'status', 'type' => 'type', 'supplier_id' => 'supplier_id'];

    public function store(Request $request, AuditService $audit)
    {
        $companyId = $this->companyId($request);
        $payload   = $this->validatedInput($request);

        $receipt = DB::transaction(function () use ($payload, $companyId, $request) {
            $payload['company_id'] = $companyId;
            $payload['user_id']    = $request->user()->id;
            $payload['status']     ??= 'draft';
            $payload['number']     ??= 'RC-'.date('Ymd').'-'.random_int(1000, 9999);

            $items = $payload['items'] ?? [];
            unset($payload['items']);

            $receipt = PurchaseReceipt::create($payload);

            foreach ($items as $item) {
                PurchaseReceiptItem::create([
                    'purchase_receipt_id' => $receipt->id,
                    'product_id'          => $item['product_id'],
                    'quantity'            => $item['quantity'],
                    'unit_cost'           => $item['unit_cost'],
                ]);
            }

            return $receipt->load($this->with);
        });

        $audit->record('created', $receipt, $request);

        return (new PurchaseReceiptResource($receipt))->response()->setStatusCode(201);
    }

    /** POST /purchase-receipts/{id}/post — genera movimientos de stock */
    public function post(Request $request, string $id, PurchaseService $purchases, AuditService $audit)
    {
        $receipt = PurchaseReceipt::where('company_id', $this->companyId($request))->findOrFail($id);

        try {
            $purchases->postReceipt($receipt, $request->user()->id);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $audit->record('posted', $receipt->fresh(), $request);

        return new PurchaseReceiptResource($receipt->fresh()->load($this->with));
    }
}
