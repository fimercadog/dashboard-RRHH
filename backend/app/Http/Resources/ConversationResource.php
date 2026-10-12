<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'contact_name'          => $this->contact_name,
            'contact_phone'         => $this->contact_phone,
            'channel'               => $this->channel,
            'status'                => $this->status,
            'assigned_to'           => $this->assigned_to,
            'assigned_user'         => $this->whenLoaded('assignedUser', fn () => [
                'id'   => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ]),
            'last_message_at'       => $this->last_message_at?->toIso8601String(),
            'last_customer_msg_at'  => $this->last_customer_msg_at?->toIso8601String(),
            'within_24h_window'     => $this->isWithin24hWindow(),
            'last_message_preview'  => $this->whenLoaded('latestMessage', fn () => $this->latestMessage?->content),
            'whatsapp_setting_id'   => $this->whatsapp_setting_id,
            'created_at'            => $this->created_at?->toIso8601String(),
        ];
    }
}
