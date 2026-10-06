<?php

namespace App\Http\Requests;

class StorePurchaseInvoiceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id'         => ['required', 'integer', $this->ownedExists('suppliers')],
            'purchase_order_id'   => ['nullable', 'integer', $this->ownedExists('purchase_orders')],
            'purchase_receipt_id' => ['nullable', 'integer', $this->ownedExists('purchase_receipts')],
            'number'              => ['required', 'string', 'max:60'],
            'date'                => ['required', 'date'],
            'due_date'            => ['required', 'date'],
            'subtotal'            => ['required', 'numeric', 'min:0'],
            'tax'                 => ['nullable', 'numeric', 'min:0'],
            'total'               => ['required', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
