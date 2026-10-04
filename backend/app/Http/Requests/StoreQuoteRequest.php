<?php

namespace App\Http\Requests;

class StoreQuoteRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'client_id'            => ['required', 'integer', 'exists:clients,id'],
            'deal_id'              => ['nullable', 'integer', 'exists:deals,id'],
            'date'                 => ['required', 'date'],
            'valid_until'          => ['required', 'date'],
            'status'               => ['nullable', 'string'],
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
