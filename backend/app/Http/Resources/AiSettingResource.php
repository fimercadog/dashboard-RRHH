<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'provider'       => $this->provider,
            'model'          => $this->model,
            // api_key NUNCA se expone al frontend.
            'has_api_key'    => filled($this->api_key),
            'base_url'       => $this->base_url,
            'is_active'      => $this->is_active,
            'last_tested_at' => $this->last_tested_at?->toIso8601String(),
            'is_connected'   => $this->isConnected(),
            'providers'      => \App\Models\AiSetting::PROVIDERS,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
