<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'identification_type' => ['required', 'string', 'max:50'],
            'identification_number' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email'],
            'hire_date' => ['required', 'date'],
            'employment_status' => ['required', 'in:active,inactive,terminated,on_leave'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
