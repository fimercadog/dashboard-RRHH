<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountingAccountConfigRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'config_key' => ['required', 'string', 'max:60'],
            'account_id' => ['required', 'integer'],
        ];
    }
}
