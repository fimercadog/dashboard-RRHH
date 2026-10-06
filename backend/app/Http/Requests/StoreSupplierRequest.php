<?php

namespace App\Http\Requests;

class StoreSupplierRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:150'],
            'nit'           => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'address'       => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'status'        => ['required', 'in:active,inactive'],
        ];
    }
}
