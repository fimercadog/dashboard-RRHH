<?php

namespace App\Services;

use App\Contracts\WhatsappServiceContract;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Http;

class WhatsappService implements WhatsappServiceContract
{
    public function sendText(WhatsappSetting $setting, string $to, string $body, int $expectedCompanyId): string
    {
        if ($setting->company_id !== $expectedCompanyId) {
            throw new \InvalidArgumentException('WhatsappSetting company mismatch.');
        }

        $response = Http::timeout(15)
            ->withToken($setting->access_token)
            ->post(
                "https://graph.facebook.com/v20.0/{$setting->phone_number_id}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $body],
                ]
            );

        if (! $response->successful()) {
            throw new \RuntimeException('WhatsApp message delivery failed.');
        }

        return $response->json('messages.0.id');
    }
}
