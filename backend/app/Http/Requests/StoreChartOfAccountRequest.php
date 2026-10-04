<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'             => ['required', 'string', 'max:20'],
            'name'             => ['required', 'string', 'max:150'],
            'type'             => ['required', 'in:asset,liability,equity,revenue,expense,cost'],
            'nature'           => ['required', 'in:debit,credit'],
            'parent_id'        => ['nullable', 'integer'],
            'level'            => ['nullable', 'integer', 'min:1', 'max:8'],
            'allows_movements' => ['boolean'],
            'status'           => ['in:active,inactive'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
