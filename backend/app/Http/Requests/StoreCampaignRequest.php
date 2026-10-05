<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'type'             => ['required', 'string', 'in:' . implode(',', Campaign::TYPES)],
            'audience_source'  => ['required', 'string', 'in:' . implode(',', Campaign::SOURCES)],
            'audience_filters' => ['nullable', 'array'],
            'message_subject'  => ['nullable', 'string', 'max:255'],
            'message_body'     => ['nullable', 'string'],
            'scheduled_at'     => ['nullable', 'date'],
        ];
    }
}
