<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'payable_type' => $this->payable_type,
            'payable_id'   => $this->payable_id,
            'amount'       => $this->amount,
            'date'         => $this->date,
            'method'       => $this->method,
            'reference'    => $this->reference,
            'notes'        => $this->notes,
            'status'       => $this->status,
            'cash_account' => $this->whenLoaded('cashAccount', fn () => [
                'id'   => $this->cashAccount->id,
                'name' => $this->cashAccount->name,
            ]),
            'user'         => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at'   => $this->created_at,
        ];
    }
}
