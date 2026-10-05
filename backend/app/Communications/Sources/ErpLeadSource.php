<?php

namespace App\Communications\Sources;

use App\Contracts\AudienceSource;
use App\Models\Lead;

class ErpLeadSource implements AudienceSource
{
    public function name(): string { return 'Leads del ERP'; }
    public function key(): string  { return 'erp_leads'; }
    public function isReady(): bool { return true; }
    public function notReadyMessage(): string { return ''; }

    public function preview(int $companyId, array $filters = []): array
    {
        $query = Lead::where('company_id', $companyId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $total = $query->count();
        $sample = $query->limit(5)->get(['id', 'name', 'email'])
            ->map(fn ($l) => ['name' => $l->name, 'email' => $l->email])
            ->all();

        return ['count' => $total, 'sample' => $sample];
    }
}
