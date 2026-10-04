<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'amount'       => $this->amount,
            'date'         => $this->date,
            'reference'    => $this->reference,
            'notes'        => $this->notes,
            'status'       => $this->status,
            'from_account' => $this->whenLoaded('fromAccount', fn () => [
                'id'   => $this->fromAccount->id,
                'name' => $this->fromAccount->name,
            ]),
            'to_account'   => $this->whenLoaded('toAccount', fn () => [
                'id'   => $this->toAccount->id,
                'name' => $this->toAccount->name,
            ]),
            'user'         => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at'   => $this->created_at,
        ];
    }
}
