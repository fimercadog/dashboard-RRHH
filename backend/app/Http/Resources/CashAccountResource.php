<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'type'       => $this->type,
            'currency'   => $this->currency,
            'balance'    => $this->balance,
            'status'     => $this->status,
            'notes'      => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
