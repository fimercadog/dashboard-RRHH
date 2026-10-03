<?php

namespace App\Http\Requests;

use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'            => ['required', 'integer', 'min:1'],
            'owner_id'             => ['nullable', 'integer', 'min:1'],
            'title'                => ['required', 'string', 'max:200'],
            'amount'               => ['nullable', 'numeric', 'min:0'],
            'stage'                => ['nullable', Rule::in(Deal::STAGES)],
            'expected_close_date'  => ['nullable', 'date'],
            'notes'                => ['nullable', 'string', 'max:5000'],
        ];
    }
}
