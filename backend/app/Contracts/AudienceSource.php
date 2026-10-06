<?php

namespace App\Contracts;

interface AudienceSource
{
    /** Human-readable source name shown in the UI. */
    public function name(): string;

    /** Unique key used in campaigns.audience_source column. */
    public function key(): string;

    /** Whether this source is currently usable (credentials/config ready). */
    public function isReady(): bool;

    /** Message shown when isReady() is false. */
    public function notReadyMessage(): string;

    /**
     * Preview recipients count and sample for the given company.
     *
     * @param  array<string, mixed>  $filters
     * @return array{count: int, sample: array<int, array{name: string, email: string|null}>}
     */
    public function preview(int $companyId, array $filters = []): array;
}
