<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoomSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'account_id'       => $this->account_id,
            'client_id'        => $this->client_id,
            // client_secret nunca se expone al frontend
            'has_client_secret' => filled($this->client_secret),
            'is_active'        => $this->is_active,
            'is_connected'     => $this->isConnected(),
            'connected_at'     => $this->connected_at?->toIso8601String(),
        ];
    }
}
