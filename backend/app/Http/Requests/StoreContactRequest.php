<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'min:1'],
            'name'      => ['required', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'email'     => ['nullable', 'email', 'max:150'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ];
    }
}
