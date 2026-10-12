<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsappSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorización por `can:whatsapp.manage` en la ruta
    }

    public function rules(): array
    {
        return [
            'phone_number_id'      => ['nullable', 'string', 'max:64'],
            'waba_id'              => ['nullable', 'string', 'max:64'],
            'access_token'         => ['nullable', 'string', 'max:2048'],
            'webhook_verify_token' => ['nullable', 'string', 'min:20', 'max:128'],
            'is_active'            => ['boolean'],
        ];
    }
}
