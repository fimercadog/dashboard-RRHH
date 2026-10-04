<?php

namespace App\Http\Requests;

class StoreCategoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', $this->ownedExists('categories')],
            'status'    => ['required', 'in:active,inactive'],
        ];
    }
}
