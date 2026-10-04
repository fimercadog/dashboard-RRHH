<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'nit'           => $this->nit,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'address'       => $this->address,
            'payment_terms' => $this->payment_terms,
            'status'        => $this->status,
            'created_at'    => $this->created_at,
        ];
    }
}
