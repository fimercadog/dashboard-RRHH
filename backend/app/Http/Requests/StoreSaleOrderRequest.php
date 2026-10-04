<?php

namespace App\Http\Requests;

class StoreSaleOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_id'            => ['required', 'integer', $this->ownedExists('clients')],
            'quote_id'             => ['nullable', 'integer', $this->ownedExists('quotes')],
            'warehouse_id'         => ['required', 'integer', $this->ownedExists('warehouses')],
            'date'                 => ['required', 'date'],
            'status'               => ['nullable', 'string'],
            'tax'                  => ['nullable', 'numeric', 'min:0'],
            'notes'                => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', $this->ownedExists('products')],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
