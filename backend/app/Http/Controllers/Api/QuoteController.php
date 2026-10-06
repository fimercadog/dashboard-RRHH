<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends BaseCrudController
{
    protected string $model    = Quote::class;
    protected string $resource = QuoteResource::class;
    protected array $with       = ['client:id,first_name,last_name', 'items.product:id,sku,name'];
    protected array $searchable = ['number', 'notes'];
    protected array $filterable = ['status' => 'status', 'client_id' => 'client_id'];

    public function store(Request $request, AuditService $audit)
    {
        $companyId = $this->companyId($request);

        // Idempotency guard: sync replay of an offline-queued quote.
        if ($request->filled('client_uuid')) {
            $existing = Quote::where('client_uuid', $request->client_uuid)
                ->where('company_id', $companyId)
                ->first();
            if ($existing) {
                return (new QuoteResource($existing->load($this->with)))->response()->setStatusCode(200);
            }
        }

        $payload   = $this->validatedInput($request);

        $quote = DB::transaction(function () use ($payload, $companyId, $request) {
            $payload['company_id'] = $companyId;
            $payload['user_id']    = $request->user()->id;
            $payload['status']     ??= 'draft';
            $payload['number']     ??= 'COT-'.date('Ymd').'-'.random_int(1000, 9999);

            $items = $payload['items'] ?? [];
            unset($payload['items']);

            [$subtotal, $discountTotal] = $this->calcTotals($items);
            $payload['subtotal']       = $subtotal;
            $payload['discount_total'] = $discountTotal;
            $payload['tax']            ??= 0;
            $payload['total']          = $subtotal - $discountTotal + ($payload['tax'] ?? 0);

            $quote = Quote::create($payload);

            foreach ($items as $item) {
                $disc = ($item['discount_pct'] ?? 0) / 100;
                QuoteItem::create([
                    'quote_id'     => $quote->id,
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'subtotal'     => $item['quantity'] * $item['unit_price'] * (1 - $disc),
                ]);
            }

            return $quote->load($this->with);
        });

        $audit->record('created', $quote, $request);

        return (new QuoteResource($quote))->response()->setStatusCode(201);
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
