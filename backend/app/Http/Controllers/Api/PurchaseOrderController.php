<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends BaseCrudController
{
    protected string $model    = PurchaseOrder::class;
    protected string $resource = PurchaseOrderResource::class;
    protected array $with       = ['supplier:id,name', 'warehouse:id,name', 'items.product:id,sku,name'];
    protected array $searchable = ['number', 'notes'];
    protected array $filterable = ['status' => 'status', 'supplier_id' => 'supplier_id'];

    /** Override store to handle nested items and auto-generate number. */
    public function store(Request $request, AuditService $audit)
    {
        $companyId = $this->companyId($request);
        $payload   = $this->validatedInput($request);

        $order = DB::transaction(function () use ($payload, $companyId, $request) {
            $payload['company_id'] = $companyId;
            $payload['user_id']    = $request->user()->id;
            $payload['status']     ??= 'draft';
            $payload['number']     ??= 'OC-'.date('Ymd').'-'.random_int(1000, 9999);

            $items = $payload['items'] ?? [];
            unset($payload['items']);

            $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_cost']);
            $payload['subtotal'] = $subtotal;
            $payload['tax']      ??= 0;
            $payload['total']    = $subtotal + ($payload['tax'] ?? 0);

            $order = PurchaseOrder::create($payload);

            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'unit_cost'         => $item['unit_cost'],
                    'subtotal'          => $item['quantity'] * $item['unit_cost'],
                ]);
            }

            return $order->load($this->with);
        });

        $audit->record('created', $order, $request);

        return (new PurchaseOrderResource($order))->response()->setStatusCode(201);
    }
}
