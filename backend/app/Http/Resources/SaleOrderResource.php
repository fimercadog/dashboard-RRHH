<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'number'         => $this->number,
            'date'           => $this->date?->toDateString(),
            'status'         => $this->status,
            'subtotal'       => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax'            => $this->tax,
            'total'          => $this->total,
            'notes'          => $this->notes,
            'client'         => $this->whenLoaded('client', fn() => ['id' => $this->client->id, 'name' => $this->client->name]),
            'warehouse'      => $this->whenLoaded('warehouse', fn() => ['id' => $this->warehouse->id, 'name' => $this->warehouse->name]),
            'quote'          => $this->whenLoaded('quote', fn() => ['id' => $this->quote->id, 'number' => $this->quote->number]),
            'items'          => $this->whenLoaded('items'),
            'created_at'     => $this->created_at,
        ];
    }
}
