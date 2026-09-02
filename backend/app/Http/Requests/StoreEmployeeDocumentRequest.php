<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreEmployeeDocumentRequest extends ApiFormRequest
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
            'employee_id' => ['required', 'exists:employees,id'],
            'document_type' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:160'],
            'file_path' => ['required', 'string'],
            'issue_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date'],
            'status' => ['required', 'in:valid,expiring,expired,pending_review'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
