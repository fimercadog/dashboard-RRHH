<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorización por `can:conversations.manage` en la ruta
    }

    public function rules(): array
    {
        $companyId = (int) ($this->user()?->company_id ?? 0);

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'El usuario no existe o no pertenece a esta empresa.',
        ];
    }
}
