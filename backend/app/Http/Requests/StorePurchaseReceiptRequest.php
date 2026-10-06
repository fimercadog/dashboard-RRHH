<?php

namespace App\Http\Requests;

class StorePurchaseReceiptRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['nullable', 'integer', $this->ownedExists('purchase_orders')],
            'supplier_id'       => ['required', 'integer', $this->ownedExists('suppliers')],
            'warehouse_id'      => ['required', 'integer', $this->ownedExists('warehouses')],
            'date'              => ['required', 'date'],
            'type'              => ['required', 'in:receipt,return'],
            'notes'             => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', $this->ownedExists('products')],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
