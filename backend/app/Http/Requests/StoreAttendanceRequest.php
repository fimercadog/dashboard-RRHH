<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreAttendanceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'employee_id' => ['required', $this->ownedExists('employees')],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:present,absent,late,permission,vacation,sick_leave'],
            'late_minutes' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:80'],
        ];
    }
}
