<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorización por `can:conversations.manage` en la ruta
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:4000'],
        ];
    }
}
