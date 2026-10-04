<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'integer', 'min:1'],
            'to_account_id'   => ['required', 'integer', 'min:1', 'different:from_account_id'],
            'amount'          => ['required', 'numeric', 'min:0.0001'],
            'date'            => ['required', 'date'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ];
    }
}
