<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'type'     => ['required', 'in:cash,bank,savings'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status'   => ['sometimes', 'in:active,inactive'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
