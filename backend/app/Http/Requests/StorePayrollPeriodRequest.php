<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:100',
            'period_type'  => 'required|in:monthly,biweekly,weekly',
            'start_date'   => 'required|date_format:Y-m-d',
            'end_date'     => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'payment_date' => 'required|date_format:Y-m-d',
            'notes'        => 'nullable|string|max:1000',
            // status, closed_*, authorized_reversal, reversal_* solo via endpoints dedicados
        ];
    }
}
