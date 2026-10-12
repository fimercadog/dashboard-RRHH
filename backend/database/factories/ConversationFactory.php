<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\WhatsappSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $whatsapp = WhatsappSetting::factory()->create();

        return [
            'company_id'           => $whatsapp->company_id,
            'whatsapp_setting_id'  => $whatsapp->id,
            'contact_name'         => fake()->name(),
            'contact_phone'        => fake()->numerify('573#########'),
            'channel'              => 'whatsapp',
            'status'               => 'open',
            'assigned_to'          => null,
            'last_message_at'      => null,
            'last_customer_msg_at' => null,
            'meta_conversation_id' => null,
        ];
    }
}
