<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeContractRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id'         => 'required|integer|min:1',
            'contract_number'     => 'required|string|max:50',
            'contract_type'       => 'required|in:indefinido,fijo,obra_labor,aprendizaje,prestacion_servicios',
            'salary_modality'     => 'required|in:ordinary,integral',
            'start_date'          => 'required|date_format:Y-m-d',
            'end_date'            => 'nullable|date_format:Y-m-d|after:start_date',
            'salary'              => 'required|numeric|min:0',
            'salary_type'         => 'required|in:monthly,daily,hourly',
            'work_hours_per_week' => 'required|integer|min:1|max:60',
            'risk_class'          => 'required|integer|in:1,2,3,4,5',
            'status'              => 'sometimes|in:active,terminated,suspended',
            'termination_reason'  => 'nullable|string|max:500',
            'notes'               => 'nullable|string|max:1000',
        ];
    }
}
