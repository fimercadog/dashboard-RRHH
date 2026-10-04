<?php

namespace App\Http\Requests;

class StorePurchaseOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_uuid'   => ['nullable', 'uuid'],
            'supplier_id'   => ['required', 'integer', $this->ownedExists('suppliers')],
            'warehouse_id'  => ['required', 'integer', $this->ownedExists('warehouses')],
            'date'          => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', $this->ownedExists('products')],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'   => ['required', 'numeric', 'min:0'],
        ];
    }
}
