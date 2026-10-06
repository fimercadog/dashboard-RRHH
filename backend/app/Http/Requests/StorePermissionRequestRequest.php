<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StorePermissionRequestRequest extends ApiFormRequest
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
            'type' => ['required', 'in:paid,unpaid,personal,bereavement,medical,study,other'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'requested_days' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,approved,rejected,cancelled'],
        ];
    }
}
