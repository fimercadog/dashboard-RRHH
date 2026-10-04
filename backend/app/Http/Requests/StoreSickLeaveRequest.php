<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreSickLeaveRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', $this->ownedExists('employees')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'document_path' => ['nullable', 'string'],
            'status' => ['required', 'in:active,closed,rejected'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
