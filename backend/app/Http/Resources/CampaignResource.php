<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'type'             => $this->type,
            'status'           => $this->status,
            'audience_source'  => $this->audience_source,
            'audience_filters' => $this->audience_filters,
            'message_subject'  => $this->message_subject,
            'message_body'     => $this->message_body,
            'scheduled_at'     => $this->scheduled_at?->toIso8601String(),
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'created_by'       => $this->creator?->name,
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
