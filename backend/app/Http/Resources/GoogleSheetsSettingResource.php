<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleSheetsSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'has_api_key' => filled($this->api_key),
            'is_active'   => $this->is_active,
            'is_connected' => $this->isConnected(),
            'connected_at' => $this->connected_at?->toIso8601String(),
        ];
    }
}
