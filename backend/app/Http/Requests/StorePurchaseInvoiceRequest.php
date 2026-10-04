<?php

namespace App\Http\Requests;

class StorePurchaseInvoiceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id'         => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_order_id'   => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'purchase_receipt_id' => ['nullable', 'integer', 'exists:purchase_receipts,id'],
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
