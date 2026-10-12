<?php

namespace App\Jobs;

use App\Contracts\WhatsappServiceContract;
use App\Models\AiSetting;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\WhatsappSetting;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly array $payload,
        private readonly int $settingId,
    ) {}

    public function retryUntil(): \DateTime
    {
        return new \DateTime('+10 minutes');
    }

    public function handle(WhatsappServiceContract $whatsappService, AiService $aiService): void
    {
        $setting = WhatsappSetting::find($this->settingId);
        if (! $setting || ! $setting->is_active) {
            return;
        }

        foreach ($this->payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    $this->handleInboundMessage($message, $value, $setting, $whatsappService, $aiService);
                }
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->handleStatusUpdate($status, $setting);
                }
            }
        }
    }

    private function handleInboundMessage(
        array $message,
        array $value,
        WhatsappSetting $setting,
        WhatsappServiceContract $whatsappService,
        AiService $aiService,
    ): void {
        $metaMessageId = $message['id'];
        $from          = $message['from'];
        $type          = $message['type'] ?? 'unknown';

        // company_id ALWAYS comes from the setting, never from the payload.
        $conversation = Conversation::firstOrCreate(
            ['whatsapp_setting_id' => $setting->id, 'contact_phone' => $from],
            [
                'company_id'   => $setting->company_id,
                'contact_name' => $value['contacts'][0]['profile']['name'] ?? null,
                'channel'      => 'whatsapp',
                'status'       => 'open',
            ]
        );

        // Capture 24h window status BEFORE updating last_customer_msg_at.
        // Null means a brand-new first contact — always within the window.
        $withinWindow = $conversation->last_customer_msg_at === null
            || $conversation->isWithin24hWindow();

        $conversation->update([
            'last_message_at'      => now(),
            'last_customer_msg_at' => now(),
            'contact_name'         => $value['contacts'][0]['profile']['name'] ?? $conversation->contact_name,
        ]);

        try {
            ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'inbound',
                'content'         => $type === 'text' ? ($message['text']['body'] ?? '') : null,
                'content_type'    => in_array($type, ['text', 'image', 'document', 'audio', 'video']) ? $type : 'text',
                'sender_type'     => 'contact',
                'meta_message_id' => $metaMessageId,
                'status'          => 'delivered',
                'meta_media_id'   => $message['image']['id'] ?? $message['document']['id'] ?? null,
                'meta_mime_type'  => $message['image']['mime_type'] ?? $message['document']['mime_type'] ?? null,
                'meta_caption'    => $message['image']['caption'] ?? $message['document']['caption'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            return; // Duplicate (Meta retry or concurrency) — ignore.
        }

        // Only reply with AI for text messages with no human assigned and within 24h window.
        if ($type !== 'text') {
            return;
        }

        if ($conversation->assigned_to !== null) {
            return;
        }

        if (! $withinWindow) {
            Log::info("WhatsApp: outside 24h window, conversation {$conversation->id}. Template required.");
            return;
        }

        $aiSetting = AiSetting::where('company_id', $setting->company_id)
            ->where('is_active', true)
            ->first();

        if (! $aiSetting) {
            return;
        }

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($msg) => [
                'role'    => $msg->sender_type === 'contact' ? 'user' : 'assistant',
                'content' => $msg->content ?? '',
            ])
            ->toArray();

        try {
            $aiReply = $aiService->complete($aiSetting, $history, $setting->company_id);
        } catch (\Throwable) {
            Log::error("WhatsApp AI completion failed for conversation {$conversation->id}.");
            return;
        }

        try {
            $wamid = $whatsappService->sendText($setting, $from, $aiReply, $setting->company_id);
        } catch (\Throwable) {
            Log::error("WhatsApp send failed for conversation {$conversation->id}.");
            return;
        }

        if (! $wamid) {
            Log::warning("WhatsApp send returned no wamid for conversation {$conversation->id}. Outbound not recorded.");
            return;
        }

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'content'         => $aiReply,
            'content_type'    => 'text',
            'sender_type'     => 'ai',
            'meta_message_id' => $wamid,
            'status'          => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    private function handleStatusUpdate(array $status, WhatsappSetting $setting): void
    {
        $newStatus = match ($status['status'] ?? '') {
            'sent'      => 'sent',
            'delivered' => 'delivered',
            'read'      => 'read',
            'failed'    => 'failed',
            default     => null,
        };

        if (! $newStatus) {
            return;
        }

        ConversationMessage::whereHas('conversation', fn ($q) => $q->where('company_id', $setting->company_id))
            ->where('meta_message_id', $status['id'])
            ->update(['status' => $newStatus]);
    }
}
