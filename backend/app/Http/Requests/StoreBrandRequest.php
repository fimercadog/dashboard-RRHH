<?php

namespace App\Http\Requests;

class StoreBrandRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
