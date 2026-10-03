<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'deal_id'    => ['nullable', 'integer', 'min:1'],
            'client_id'  => ['nullable', 'integer', 'min:1'],
            'contact_id' => ['nullable', 'integer', 'min:1'],
            'type'       => ['nullable', Rule::in(Activity::TYPES)],
            'title'      => ['required', 'string', 'max:200'],
            'body'       => ['nullable', 'string', 'max:5000'],
            'due_at'     => ['nullable', 'date'],
            'done_at'    => ['nullable', 'date'],
            'status'     => ['nullable', Rule::in(Activity::STATUSES)],
        ];
    }
}
