<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationMessageFactory extends Factory
{
    protected $model = ConversationMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'direction'       => 'inbound',
            'content'         => fake()->sentence(),
            'content_type'    => 'text',
            'sender_type'     => 'contact',
            'meta_message_id' => fake()->uuid(),
            'status'          => 'pending',
            'error_message'   => null,
            'meta_media_id'   => null,
            'meta_mime_type'  => null,
            'meta_caption'    => null,
        ];
    }
}
