<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'number'        => $this->number,
            'date'          => $this->date,
            'expected_date' => $this->expected_date,
            'status'        => $this->status,
            'subtotal'      => $this->subtotal,
            'tax'           => $this->tax,
            'total'         => $this->total,
            'notes'         => $this->notes,
            'supplier'      => $this->whenLoaded('supplier', fn() => ['id' => $this->supplier->id, 'name' => $this->supplier->name]),
            'warehouse'     => $this->whenLoaded('warehouse', fn() => ['id' => $this->warehouse->id, 'name' => $this->warehouse->name]),
            'items'         => $this->whenLoaded('items'),
            'created_at'    => $this->created_at,
        ];
    }
}
