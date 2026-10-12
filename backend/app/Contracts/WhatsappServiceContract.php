<?php

namespace App\Contracts;

use App\Models\WhatsappSetting;

interface WhatsappServiceContract
{
    /**
     * Send a plain-text message via the WhatsApp Cloud API.
     *
     * @param  WhatsappSetting  $setting           Company's WhatsApp credentials.
     * @param  string           $to                Recipient phone number (E.164, no "+").
     * @param  string           $body              Text content.
     * @param  int              $expectedCompanyId Guard against cross-company misuse.
     * @return string                              Meta wamid (e.g. "wamid.abc123").
     *
     * @throws \InvalidArgumentException If $setting belongs to a different company.
     * @throws \RuntimeException         On non-2xx response.
     */
    public function sendText(WhatsappSetting $setting, string $to, string $body, int $expectedCompanyId): string;
}
