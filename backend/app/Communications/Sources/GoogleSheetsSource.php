<?php

namespace App\Communications\Sources;

use App\Contracts\AudienceSource;

/**
 * Google Sheets audience source — STUB.
 *
 * Architecture is ready. To enable, configure:
 *   GOOGLE_SHEETS_API_KEY and GOOGLE_SHEETS_SPREADSHEET_ID in .env
 * Then implement preview() using the Google Sheets API v4.
 */
class GoogleSheetsSource implements AudienceSource
{
    public function name(): string { return 'Google Sheets'; }
    public function key(): string  { return 'google_sheets'; }

    public function isReady(): bool
    {
        return ! empty(config('services.google.sheets_api_key'));
    }

    public function notReadyMessage(): string
    {
        return 'Integración preparada — requiere GOOGLE_SHEETS_API_KEY en la configuración del servidor.';
    }

    public function preview(int $companyId, array $filters = []): array
    {
        // ponytail: throws until credentials are configured; caller checks isReady() first.
        throw new \RuntimeException($this->notReadyMessage());
    }
}
