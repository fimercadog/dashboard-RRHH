<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreEmployeeDocumentRequest extends ApiFormRequest
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
            'document_type' => ['required', 'in:contrato,hoja_vida,diploma,certificado,soporte_disciplinario,otro'],
            'name' => ['required', 'string', 'max:160'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'issue_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date'],
            'status' => ['required', 'in:valid,expiring,expired,pending_review'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
