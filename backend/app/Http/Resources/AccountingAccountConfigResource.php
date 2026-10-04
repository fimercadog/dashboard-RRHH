<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountingAccountConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'config_key'   => $this->config_key,
            'account_id'   => $this->account_id,
            'account_code' => $this->account?->code,
            'account_name' => $this->account?->name,
            'created_at'   => $this->created_at,
        ];
    }
}
