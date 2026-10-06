<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'quantity'       => $this->quantity,
            'unit_cost'      => $this->unit_cost,
            'total_cost'     => $this->total_cost,
            'transfer_id'    => $this->transfer_id,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'notes'          => $this->notes,
            'posted_at'      => $this->posted_at,
            'created_at'     => $this->created_at,
            'product'        => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id, 'sku' => $this->product->sku, 'name' => $this->product->name,
            ]),
            'warehouse'      => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id, 'name' => $this->warehouse->name,
            ]),
            'user'           => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id, 'name' => $this->user->name,
            ]),
        ];
    }
}
