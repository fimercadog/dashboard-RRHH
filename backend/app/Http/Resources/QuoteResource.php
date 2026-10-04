<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'number'         => $this->number,
            'date'           => $this->date?->toDateString(),
            'valid_until'    => $this->valid_until?->toDateString(),
            'status'         => $this->status,
            'subtotal'       => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax'            => $this->tax,
            'total'          => $this->total,
            'notes'          => $this->notes,
            'client'         => $this->whenLoaded('client', fn() => ['id' => $this->client->id, 'name' => $this->client->name]),
            'deal'           => $this->whenLoaded('deal', fn() => ['id' => $this->deal->id, 'title' => $this->deal->title]),
            'items'          => $this->whenLoaded('items'),
            'created_at'     => $this->created_at,
        ];
    }
}
