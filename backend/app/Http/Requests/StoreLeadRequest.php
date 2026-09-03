<?php

namespace App\Http\Requests;

class StoreLeadRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'employee_count' => ['nullable', 'string', 'max:50'],
            'priority_module' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', 'in:contact,demo'],
            // Ley 1581: el consentimiento es obligatorio para guardar el lead.
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'consent.accepted' => 'Debes autorizar el tratamiento de datos para enviar el formulario.',
        ];
    }
}
