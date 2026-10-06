<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'amount'         => $this->amount,
            'date'           => $this->date,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'description'    => $this->description,
            'cash_account'   => $this->whenLoaded('cashAccount', fn () => [
                'id'   => $this->cashAccount->id,
                'name' => $this->cashAccount->name,
            ]),
            'user'           => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at'     => $this->created_at,
        ];
    }
}
