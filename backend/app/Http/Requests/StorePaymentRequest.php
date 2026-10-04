<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payable_type'    => ['required', 'in:accounts_receivable,accounts_payable'],
            'payable_id'      => ['required', 'integer', 'min:1'],
            'cash_account_id' => ['required', 'integer', 'min:1'],
            'amount'          => ['required', 'numeric', 'min:0.0001'],
            'date'            => ['required', 'date'],
            'method'          => ['required', 'in:cash,transfer,check,card,other'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ];
    }
}
