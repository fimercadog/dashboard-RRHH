<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'phone_number_id'      => $this->phone_number_id,
            'waba_id'              => $this->waba_id,
            // access_token y webhook_verify_token NUNCA se exponen al frontend.
            'has_access_token'     => filled($this->access_token),
            'has_webhook_token'    => filled($this->webhook_verify_token),
            'is_active'            => $this->is_active,
            'webhook_verified_at'  => $this->webhook_verified_at?->toIso8601String(),
            'is_connected'         => $this->isConnected(),
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
