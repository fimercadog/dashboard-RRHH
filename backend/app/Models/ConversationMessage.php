<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'direction',
        'content',
        'content_type',
        'sender_type',
        'meta_message_id',
        'status',
        'error_message',
        'meta_media_id',
        'meta_mime_type',
        'meta_caption',
    ];

    protected $casts = [
        'direction'    => 'string',
        'content_type' => 'string',
        'sender_type'  => 'string',
        'status'       => 'string',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
