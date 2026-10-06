<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'number'            => $this->number,
            'date'              => $this->date,
            'type'              => $this->type,
            'status'            => $this->status,
            'notes'             => $this->notes,
            'purchase_order_id' => $this->purchase_order_id,
            'warehouse'         => $this->whenLoaded('warehouse', fn() => ['id' => $this->warehouse->id, 'name' => $this->warehouse->name]),
            'supplier'          => $this->whenLoaded('supplier', fn() => ['id' => $this->supplier->id, 'name' => $this->supplier->name]),
            'items'             => $this->whenLoaded('items'),
            'created_at'        => $this->created_at,
        ];
    }
}
