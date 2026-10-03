<?php

namespace App\Http\Requests;

class StoreClientRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'first_name'            => ['nullable', 'string', 'max:80'],
            'last_name'             => ['nullable', 'string', 'max:80'],
            'company_name'          => ['nullable', 'string', 'max:150'],
            'identification_type'   => ['nullable', 'string', 'in:CC,CE,PA,NIT,PPT,TI,RC'],
            'identification_number' => ['nullable', 'string', 'max:30'],
            'email'                 => ['nullable', 'email', 'max:150'],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'address'               => ['nullable', 'string'],
            'city'                  => ['nullable', 'string', 'max:80'],
            'notes'                 => ['nullable', 'string'],
            'status'                => ['required', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'first_name' => 'nombres',
            'last_name'  => 'apellidos',
            'city'       => 'ciudad',
            'phone'      => 'telefono',
        ]);
    }
}
