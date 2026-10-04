<?php

namespace App\Http\Requests;

class StoreProductRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'sku'         => ['nullable', 'string', 'max:60'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', $this->ownedExists('categories')],
            'brand_id'    => ['nullable', 'integer', $this->ownedExists('brands')],
            'unit_id'     => ['nullable', 'integer', $this->ownedExists('units')],
            'type'        => ['required', 'in:storable,service,consumable'],
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'min_stock'   => ['nullable', 'numeric', 'min:0'],
            'inventory_account_code' => ['nullable', 'string', 'max:20'],
            'cogs_account_code'      => ['nullable', 'string', 'max:20'],
            'sale_account_code'      => ['nullable', 'string', 'max:20'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
