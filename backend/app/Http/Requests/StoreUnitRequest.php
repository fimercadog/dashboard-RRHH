<?php

namespace App\Http\Requests;

class StoreUnitRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:60'],
            'abbreviation' => ['required', 'string', 'max:10'],
            'status'       => ['required', 'in:active,inactive'],
        ];
    }
}
