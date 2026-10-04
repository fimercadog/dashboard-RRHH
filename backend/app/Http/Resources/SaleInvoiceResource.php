<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleInvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'number'          => $this->number,
            'date'            => $this->date?->toDateString(),
            'due_date'        => $this->due_date?->toDateString(),
            'type'            => $this->type,
            'status'          => $this->status,
            'subtotal'        => $this->subtotal,
            'discount_total'  => $this->discount_total,
            'tax'             => $this->tax,
            'total'           => $this->total,
            'notes'           => $this->notes,
            'client'          => $this->whenLoaded('client', fn() => ['id' => $this->client->id, 'name' => $this->client->name]),
            'sale_order'      => $this->whenLoaded('saleOrder', fn() => ['id' => $this->saleOrder->id, 'number' => $this->saleOrder->number]),
            'items'           => $this->whenLoaded('items'),
            'account_receivable' => $this->whenLoaded('accountReceivable'),
            'created_at'      => $this->created_at,
        ];
    }
}
