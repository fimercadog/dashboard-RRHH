<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'deal_id'    => ['nullable', 'integer', 'min:1', $this->ownedExists('deals')],
            'client_id'  => ['nullable', 'integer', 'min:1', $this->ownedExists('clients')],
            'contact_id' => ['nullable', 'integer', 'min:1', $this->ownedExists('contacts')],
            'type'       => ['nullable', Rule::in(Activity::TYPES)],
            'title'      => ['required', 'string', 'max:200'],
            'body'       => ['nullable', 'string', 'max:5000'],
            'due_at'     => ['nullable', 'date'],
            'done_at'    => ['nullable', 'date'],
            'status'     => ['nullable', Rule::in(Activity::STATUSES)],
        ];
    }
}
