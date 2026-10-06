<?php

namespace App\Http\Requests;

class StoreWarehouseRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'status'   => ['required', 'in:active,inactive'],
        ];
    }
}
