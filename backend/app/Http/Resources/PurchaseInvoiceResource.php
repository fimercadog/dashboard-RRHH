<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'number'              => $this->number,
            'date'                => $this->date,
            'due_date'            => $this->due_date,
            'status'              => $this->status,
            'subtotal'            => $this->subtotal,
            'tax'                 => $this->tax,
            'total'               => $this->total,
            'notes'               => $this->notes,
            'purchase_order_id'   => $this->purchase_order_id,
            'purchase_receipt_id' => $this->purchase_receipt_id,
            'supplier'            => $this->whenLoaded('supplier', fn() => ['id' => $this->supplier->id, 'name' => $this->supplier->name]),
            'account_payable'     => $this->whenLoaded('accountPayable'),
            'created_at'          => $this->created_at,
        ];
    }
}
