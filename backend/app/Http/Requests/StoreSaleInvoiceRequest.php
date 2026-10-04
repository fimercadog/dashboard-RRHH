<?php

namespace App\Http\Requests;

class StoreSaleInvoiceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_id'            => ['required', 'integer', 'exists:clients,id'],
            'sale_order_id'        => ['nullable', 'integer', 'exists:sale_orders,id'],
            'number'               => ['required', 'string', 'max:60'],
            'date'                 => ['required', 'date'],
            'due_date'             => ['required', 'date'],
            'type'                 => ['nullable', 'string', 'in:invoice,return'],
            'tax'                  => ['nullable', 'numeric', 'min:0'],
            'notes'                => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
