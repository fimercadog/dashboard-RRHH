<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'direction'       => $this->direction,
            'content'         => $this->content,
            'content_type'    => $this->content_type,
            'sender_type'     => $this->sender_type,
            'status'          => $this->status,
            'meta_message_id' => $this->meta_message_id,
            'meta_media_id'   => $this->meta_media_id,
            'meta_mime_type'  => $this->meta_mime_type,
            'meta_caption'    => $this->meta_caption,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
