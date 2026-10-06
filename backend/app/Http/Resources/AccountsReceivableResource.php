<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountsReceivableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'amount'      => $this->amount,
            'balance'     => $this->balance,
            'due_date'    => $this->due_date?->toDateString(),
            'status'      => $this->status,
            'client'      => $this->whenLoaded('client', fn() => ['id' => $this->client->id, 'name' => $this->client->full_name]),
            'sale_invoice'=> $this->whenLoaded('saleInvoice', fn() => ['id' => $this->saleInvoice->id, 'number' => $this->saleInvoice->number, 'total' => $this->saleInvoice->total]),
            'created_at'  => $this->created_at,
        ];
    }
}
