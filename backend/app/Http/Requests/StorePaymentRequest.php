<?php

namespace App\Http\Requests;

class StorePaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $payableTable = match ($this->input('payable_type')) {
            'accounts_receivable' => 'accounts_receivable',
            default               => 'accounts_payable',
        };

        return [
            'payable_type'    => ['required', 'in:accounts_receivable,accounts_payable'],
            'payable_id'      => ['required', 'integer', 'min:1', $this->ownedExists($payableTable)],
            'cash_account_id' => ['required', 'integer', 'min:1', $this->ownedExists('cash_accounts')],
            'amount'          => ['required', 'numeric', 'min:0.0001'],
            'date'            => ['required', 'date'],
            'method'          => ['required', 'in:cash,transfer,check,card,other'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ];
    }
}
