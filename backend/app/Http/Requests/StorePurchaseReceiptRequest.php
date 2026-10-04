<?php

namespace App\Http\Requests;

class StorePurchaseReceiptRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'supplier_id'       => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id'      => ['required', 'integer', 'exists:warehouses,id'],
            'date'              => ['required', 'date'],
            'type'              => ['required', 'in:receipt,return'],
            'notes'             => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
