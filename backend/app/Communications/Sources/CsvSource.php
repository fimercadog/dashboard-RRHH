<?php

namespace App\Communications\Sources;

use App\Contracts\AudienceSource;

/**
 * CSV upload audience source — STUB.
 *
 * Architecture is ready. To enable, implement preview() to parse
 * an uploaded CSV file (stored in storage/app/campaigns/) with
 * columns: name, email.
 */
class CsvSource implements AudienceSource
{
    public function name(): string { return 'Archivo CSV'; }
    public function key(): string  { return 'csv'; }

    public function isReady(): bool
    {
        // CSV upload parsing is ready to implement; file upload endpoint not yet built.
        return false;
    }

    public function notReadyMessage(): string
    {
        return 'Carga de CSV disponible próximamente — requiere endpoint de subida de archivo.';
    }

    public function preview(int $companyId, array $filters = []): array
    {
        throw new \RuntimeException($this->notReadyMessage());
    }
}
