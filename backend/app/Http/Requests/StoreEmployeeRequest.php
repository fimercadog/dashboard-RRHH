<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreEmployeeRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/'],
            'first_name' => ['required', 'string', 'max:120', 'regex:/^[\pL\pM\s.\x27\-]+$/u'],
            'last_name' => ['required', 'string', 'max:120', 'regex:/^[\pL\pM\s.\x27\-]+$/u'],
            'identification_type' => ['required', 'in:CC,CE,TI,PA,PEP,NIT,RC'],
            'identification_number' => ['required', 'string', 'max:80', 'regex:/^[0-9][0-9.\-]*$/'],
            'email' => ['nullable', 'email', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]{2,}$/i'],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', 'in:active,inactive,terminated,on_leave'],
            'department_id' => ['nullable', $this->ownedExists('departments')],
            'position_id' => ['nullable', $this->ownedExists('positions')],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ];
    }
}
