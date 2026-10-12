<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsappWebhookJob;
use App\Models\AiSetting;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Models\WhatsappSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessWhatsappWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTextPayload(string $from, string $messageId, string $body): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'contacts'          => [['profile' => ['name' => 'Test User'], 'wa_id' => $from]],
                        'messages'          => [[
                            'id'        => $messageId,
                            'from'      => $from,
                            'type'      => 'text',
                            'text'      => ['body' => $body],
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function makeImagePayload(string $from, string $messageId): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'contacts'          => [['profile' => ['name' => 'Test User'], 'wa_id' => $from]],
                        'messages'          => [[
                            'id'        => $messageId,
                            'from'      => $from,
                            'type'      => 'image',
                            'image'     => ['id' => 'media-123', 'mime_type' => 'image/jpeg', 'caption' => 'hi'],
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function makeStatusPayload(string $messageId, string $status): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id'        => $messageId,
                            'status'    => $status,
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function activeSetting(): WhatsappSetting
    {
        return WhatsappSetting::factory()->create(['is_active' => true]);
    }

    private function activeAiSetting(int $companyId): AiSetting
    {
        return AiSetting::factory()->create([
            'company_id' => $companyId,
            'is_active'  => true,
            'provider'   => 'openai',
            'model'      => 'gpt-4o-mini',
            'api_key'    => 'sk-test',
        ]);
    }

    private function fakeApis(string $aiReply = 'Hello from AI', string $wamid = 'wamid.abc'): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => $aiReply]]],
            ], 200),
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => $wamid]],
            ], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_text_message_creates_conversation_and_ai_reply(): void
    {
        $this->fakeApis('Hello from AI', 'wamid.reply1');

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        $payload = $this->makeTextPayload('573001234567', 'msg-001', 'Hola');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversations', [
            'whatsapp_setting_id' => $setting->id,
            'contact_phone'       => '573001234567',
        ]);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-001',
            'direction'       => 'inbound',
            'content'         => 'Hola',
        ]);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'wamid.reply1',
            'direction'       => 'outbound',
            'content'         => 'Hello from AI',
            'sender_type'     => 'ai',
        ]);
    }

    public function test_duplicate_message_is_ignored(): void
    {
        $this->fakeApis();

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        $payload = $this->makeTextPayload('573001234567', 'msg-dup', 'Duplicado');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);
        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertSame(
            1,
            ConversationMessage::where('meta_message_id', 'msg-dup')->count()
        );
    }

    public function test_status_update_updates_message_status(): void
    {
        $setting = $this->activeSetting();

        $conversation = Conversation::create([
            'company_id'          => $setting->company_id,
            'whatsapp_setting_id' => $setting->id,
            'contact_name'        => 'Test Contact',
            'contact_phone'       => '573000000010',
            'channel'             => 'whatsapp',
            'status'              => 'open',
            'last_message_at'     => now(),
        ]);

        $msg = ConversationMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'meta_message_id' => 'wamid.status-test',
            'status'          => 'sent',
        ]);

        $payload = $this->makeStatusPayload('wamid.status-test', 'delivered');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversation_messages', [
            'id'     => $msg->id,
            'status' => 'delivered',
        ]);
    }

    public function test_status_update_does_not_affect_other_company_messages(): void
    {
        $settingA = $this->activeSetting();
        $settingB = $this->activeSetting();

        $conversationA = Conversation::create([
            'company_id'          => $settingA->company_id,
            'whatsapp_setting_id' => $settingA->id,
            'contact_name'        => 'A Contact',
            'contact_phone'       => '573001111111',
            'channel'             => 'whatsapp',
            'status'              => 'open',
            'last_message_at'     => now(),
        ]);

        $msg = ConversationMessage::factory()->create([
            'conversation_id' => $conversationA->id,
            'meta_message_id' => 'wamid.cross-company-test',
            'status'          => 'sent',
        ]);

        // Dispatch with company B's setting — must NOT update company A's message.
        $payload = $this->makeStatusPayload('wamid.cross-company-test', 'delivered');
        ProcessWhatsappWebhookJob::dispatchSync($payload, $settingB->id);

        $this->assertDatabaseHas('conversation_messages', [
            'id'     => $msg->id,
            'status' => 'sent', // unchanged
        ]);
    }

    public function test_no_ai_reply_when_outside_24h_window(): void
    {
        // If the job incorrectly replies, Http::fake() will capture the request.
        Http::fake([
            'https://api.openai.com/*'      => Http::response(['choices' => [['message' => ['content' => 'reply']]]], 200),
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.should-not-appear']]], 200),
        ]);

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        // Create a conversation with last_customer_msg_at well outside the 24h window.
        Conversation::create([
            'company_id'           => $setting->company_id,
            'whatsapp_setting_id'  => $setting->id,
            'contact_name'         => 'Old Contact',
            'contact_phone'        => '573009999999',
            'channel'              => 'whatsapp',
            'status'               => 'open',
            'last_message_at'      => Carbon::parse('2000-01-01 00:00:00'),
            'last_customer_msg_at' => Carbon::parse('2000-01-01 00:00:00'),
        ]);

        $payload = $this->makeTextPayload('573009999999', 'msg-old-window', 'Late message');
        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        // Inbound message must be saved.
        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-old-window',
            'direction'       => 'inbound',
        ]);

        // No outbound message — outside 24h window.
        $this->assertDatabaseMissing('conversation_messages', [
            'meta_message_id' => 'wamid.should-not-appear',
            'direction'       => 'outbound',
        ]);
        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_no_ai_reply_when_assigned_to_human(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.y']]], 200),
            'https://api.openai.com/*'      => Http::response(['choices' => [['message' => ['content' => 'reply']]]], 200),
        ]);

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);
        $user = User::factory()->create(['company_id' => $setting->company_id]);

        Conversation::create([
            'company_id'           => $setting->company_id,
            'whatsapp_setting_id'  => $setting->id,
            'contact_name'         => 'Assigned Contact',
            'contact_phone'        => '573000000002',
            'channel'              => 'whatsapp',
            'status'               => 'open',
            'assigned_to'          => $user->id,
            'last_message_at'      => now(),
            'last_customer_msg_at' => now(),
        ]);

        $payload = $this->makeTextPayload('573000000002', 'msg-assigned', 'Hi agent');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-assigned',
            'direction'       => 'inbound',
        ]);

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_no_ai_reply_when_no_ai_setting(): void
    {
        Http::fake(['https://graph.facebook.com/*' => Http::response([], 200)]);

        $setting = $this->activeSetting();
        // No AiSetting created.

        $payload = $this->makeTextPayload('573000000003', 'msg-noai', 'Hello');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-noai',
            'direction'       => 'inbound',
        ]);

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_non_text_message_is_saved_but_no_ai_reply(): void
    {
        Http::fake(); // Should not be called.

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        $payload = $this->makeImagePayload('573000000004', 'msg-img');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-img',
            'direction'       => 'inbound',
            'content_type'    => 'image',
        ]);

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_ai_failure_does_not_propagate_exception(): void
    {
        Http::fake([
            'https://api.openai.com/*'     => Http::response([], 500),
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.z']]], 200),
        ]);

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        $payload = $this->makeTextPayload('573000000005', 'msg-aifail', 'Hi');

        // Should NOT throw.
        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-aifail',
            'direction'       => 'inbound',
        ]);

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_whatsapp_send_failure_does_not_propagate_exception(): void
    {
        Http::fake([
            'https://api.openai.com/*'     => Http::response([
                'choices' => [['message' => ['content' => 'Good reply']]],
            ], 200),
            'https://graph.facebook.com/*' => Http::response([], 500),
        ]);

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        $payload = $this->makeTextPayload('573000000006', 'msg-sendfail', 'Hello');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $this->assertDatabaseHas('conversation_messages', [
            'meta_message_id' => 'msg-sendfail',
            'direction'       => 'inbound',
        ]);

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_company_id_comes_from_setting_not_payload(): void
    {
        $this->fakeApis();

        $setting = $this->activeSetting();
        $this->activeAiSetting($setting->company_id);

        // Payload intentionally contains no company_id — should still work.
        $payload = $this->makeTextPayload('573000000007', 'msg-company', 'Test');

        ProcessWhatsappWebhookJob::dispatchSync($payload, $setting->id);

        $conversation = Conversation::where('contact_phone', '573000000007')->first();

        $this->assertNotNull($conversation);
        $this->assertSame($setting->company_id, $conversation->company_id);
    }
}
