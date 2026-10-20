<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyTaxProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'legal_nature'         => 'required|string|max:100',
            'tax_contributor_type' => 'required|string|max:100',
            'parafiscal_exempt'    => 'required|boolean',
            'exempt_basis'         => 'nullable|string|max:500',
            'valid_from'           => 'required|date_format:Y-m-d',
            'valid_until'          => 'nullable|date_format:Y-m-d|after:valid_from',
            'notes'                => 'nullable|string|max:1000',
            // review_status NO aparece aquí — solo via /validate
        ];
    }
}
