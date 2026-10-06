<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreDepartmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', $this->ownedExists('employees')],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
