<?php

namespace App\Communications\Sources;

use App\Contracts\AudienceSource;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Sheets audience source.
 *
 * Reads a public spreadsheet via Google Sheets API v4 (API key auth).
 * Auto-detects column headers from row 1 and maps them to the normalized
 * Recipient shape. For private sheets, OAuth2 would be required instead.
 *
 * Required config (.env):
 *   GOOGLE_SHEETS_API_KEY          — Google Cloud API key with Sheets v4 enabled
 *   GOOGLE_SHEETS_SPREADSHEET_ID   — default spreadsheet ID (can be overridden per campaign)
 *
 * Per-campaign overrides via audience_filters:
 *   spreadsheet_id  — override the default spreadsheet
 *   sheet_name      — tab name (default: "Sheet1")
 *   segment         — filter by segment column value
 *   status          — filter by status column value
 */
class GoogleSheetsSource implements AudienceSource
{
    private const API_BASE = 'https://sheets.googleapis.com/v4/spreadsheets';

    /**
     * Candidate column names (lowercase) → normalized field.
     * First match wins; order inside each list = priority.
     */
    private const COLUMN_CANDIDATES = [
        'name'    => ['nombre', 'name', 'full_name', 'fullname', 'nombre_completo', 'contacto', 'contact'],
        'phone'   => ['telefono', 'teléfono', 'phone', 'celular', 'cel', 'movil', 'móvil', 'tel'],
        'email'   => ['email', 'correo', 'mail', 'e-mail', 'correo_electronico', 'correo_electrónico'],
        'type'    => ['tipo', 'type', 'categoria', 'categoría', 'category'],
        'segment' => ['segmento', 'segment', 'grupo', 'group', 'lista', 'list'],
        'status'  => ['estado', 'status', 'activo', 'active'],
    ];

    public function name(): string { return 'Google Sheets'; }
    public function key(): string  { return 'google_sheets'; }

    public function isReady(): bool
    {
        return ! empty(config('services.google.sheets_api_key'));
    }

    public function notReadyMessage(): string
    {
        return 'Integración preparada — requiere GOOGLE_SHEETS_API_KEY en la configuración del servidor. '
            . 'La hoja debe ser pública (cualquiera con el enlace puede ver).';
    }

    /**
     * @param  array{spreadsheet_id?: string, sheet_name?: string, segment?: string, status?: string}  $filters
     * @return array{count: int, sample: list<array{name: string, email: string|null}>, columns_detected: list<string>}
     */
    public function preview(int $companyId, array $filters = []): array
    {
        $apiKey        = config('services.google.sheets_api_key');
        $spreadsheetId = $filters['spreadsheet_id']
            ?? config('services.google.sheets_spreadsheet_id')
            ?? '';
        $sheetName     = $filters['sheet_name'] ?? 'Sheet1';

        if (empty($spreadsheetId)) {
            throw new RuntimeException(
                'No spreadsheet ID configured. Set GOOGLE_SHEETS_SPREADSHEET_ID or pass spreadsheet_id in audience_filters.'
            );
        }

        $response = Http::timeout(10)->get(
            self::API_BASE . "/{$spreadsheetId}/values/{$sheetName}",
            ['key' => $apiKey]
        );

        $this->assertSuccessful($response, $sheetName);

        $rows = $response->json('values', []);

        if (empty($rows)) {
            return ['count' => 0, 'sample' => [], 'columns_detected' => []];
        }

        $rawHeaders     = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);
        $columnIndexMap = $this->detectColumnMapping($rawHeaders);
        $dataRows       = array_slice($rows, 1);
        $recipients     = $this->normalizeRows($dataRows, $columnIndexMap);

        if (! empty($filters['segment'])) {
            $seg        = mb_strtolower($filters['segment']);
            $recipients = array_values(array_filter($recipients, fn ($r) => mb_strtolower((string) ($r['segment'] ?? '')) === $seg));
        }

        if (! empty($filters['status'])) {
            $st         = mb_strtolower($filters['status']);
            $recipients = array_values(array_filter($recipients, fn ($r) => mb_strtolower((string) ($r['status'] ?? '')) === $st));
        }

        $sample = array_slice(
            array_map(fn ($r) => ['name' => $r['name'], 'email' => $r['email']], $recipients),
            0,
            5
        );

        return [
            'count'            => count($recipients),
            'sample'           => $sample,
            'columns_detected' => array_keys($columnIndexMap),
        ];
    }

    /** Map detected column headers to their 0-based column indices. */
    private function detectColumnMapping(array $headers): array
    {
        $mapping = [];
        foreach (self::COLUMN_CANDIDATES as $field => $candidates) {
            foreach ($headers as $index => $header) {
                if (in_array($header, $candidates, true)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }
        return $mapping;
    }

    /** Normalize raw row arrays into Recipient-shaped arrays. Skips empty rows. */
    private function normalizeRows(array $rows, array $mapping): array
    {
        $get = fn (array $row, string $field): ?string => isset($mapping[$field], $row[$mapping[$field]])
            ? (trim((string) $row[$mapping[$field]]) ?: null)
            : null;

        $normalized = [];
        foreach ($rows as $row) {
            $name = $get($row, 'name');
            if ($name === null) {
                continue; // skip blank rows
            }
            $normalized[] = [
                'name'    => $name,
                'phone'   => $get($row, 'phone'),
                'email'   => $get($row, 'email'),
                'type'    => $get($row, 'type'),
                'segment' => $get($row, 'segment'),
                'status'  => $get($row, 'status'),
            ];
        }
        return $normalized;
    }

    /** @throws RuntimeException */
    private function assertSuccessful(\Illuminate\Http\Client\Response $response, string $sheetName): void
    {
        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException(
                'Error de autenticación con Google Sheets API. Verifica que GOOGLE_SHEETS_API_KEY sea válida '
                . 'y que la hoja sea pública.'
            );
        }

        if ($response->status() === 404) {
            $error = $response->json('error.message', '');
            if (str_contains(strtolower($error), 'unable to parse range')) {
                throw new RuntimeException("Hoja '{$sheetName}' no encontrada en el spreadsheet.");
            }
            throw new RuntimeException('Spreadsheet no encontrado. Verifica GOOGLE_SHEETS_SPREADSHEET_ID.');
        }

        if ($response->failed()) {
            $message = $response->json('error.message', 'Error desconocido');
            throw new RuntimeException("Error al leer Google Sheets (HTTP {$response->status()}): {$message}");
        }
    }
}
