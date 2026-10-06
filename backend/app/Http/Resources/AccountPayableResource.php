<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountPayableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'amount'              => $this->amount,
            'balance'             => $this->balance,
            'due_date'            => $this->due_date,
            'status'              => $this->status,
            'purchase_invoice_id' => $this->purchase_invoice_id,
            'supplier'            => $this->whenLoaded('supplier', fn() => ['id' => $this->supplier->id, 'name' => $this->supplier->name]),
            'purchase_invoice'    => $this->whenLoaded('purchaseInvoice', fn() => ['id' => $this->purchaseInvoice->id, 'number' => $this->purchaseInvoice->number, 'total' => $this->purchaseInvoice->total]),
            'created_at'          => $this->created_at,
        ];
    }
}
