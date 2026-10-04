<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleOrderResource;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Services\AuditService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleOrderController extends BaseCrudController
{
    protected string $model    = SaleOrder::class;
    protected string $resource = SaleOrderResource::class;
    protected array $with       = ['client:id,name', 'warehouse:id,name', 'items.product:id,sku,name'];
    protected array $searchable = ['number', 'notes'];
    protected array $filterable = ['status' => 'status', 'client_id' => 'client_id'];

    public function store(Request $request, AuditService $audit)
    {
        $companyId = $this->companyId($request);
        $payload   = $this->validatedInput($request);

        $order = DB::transaction(function () use ($payload, $companyId, $request) {
            $payload['company_id'] = $companyId;
            $payload['user_id']    = $request->user()->id;
            $payload['status']     ??= 'draft';
            $payload['number']     ??= 'PE-'.date('Ymd').'-'.random_int(1000, 9999);

            $items = $payload['items'] ?? [];
            unset($payload['items']);

            [$subtotal, $discountTotal] = $this->calcTotals($items);
            $payload['subtotal']       = $subtotal;
            $payload['discount_total'] = $discountTotal;
            $payload['tax']            ??= 0;
            $payload['total']          = $subtotal - $discountTotal + ($payload['tax'] ?? 0);

            $order = SaleOrder::create($payload);

            foreach ($items as $item) {
                $disc = ($item['discount_pct'] ?? 0) / 100;
                SaleOrderItem::create([
                    'sale_order_id' => $order->id,
                    'product_id'    => $item['product_id'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'discount_pct'  => $item['discount_pct'] ?? 0,
                    'subtotal'      => $item['quantity'] * $item['unit_price'] * (1 - $disc),
                ]);
            }

            return $order->load($this->with);
        });

        $audit->record('created', $order, $request);

        return (new SaleOrderResource($order))->response()->setStatusCode(201);
    }

    public function confirm(int $id, Request $request, SaleService $service, AuditService $audit)
    {
        $order = SaleOrder::where('company_id', $this->companyId($request))->findOrFail($id);
        $service->confirmOrder($order);
        $audit->record('confirmed', $order, $request);

        return new SaleOrderResource($order->fresh($this->with));
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
