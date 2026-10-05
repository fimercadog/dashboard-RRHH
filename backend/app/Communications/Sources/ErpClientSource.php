<?php

namespace App\Communications\Sources;

use App\Contracts\AudienceSource;
use App\Models\Client;

class ErpClientSource implements AudienceSource
{
    public function name(): string { return 'Clientes del ERP'; }
    public function key(): string  { return 'erp_clients'; }
    public function isReady(): bool { return true; }
    public function notReadyMessage(): string { return ''; }

    public function preview(int $companyId, array $filters = []): array
    {
        $query = Client::where('company_id', $companyId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $total = $query->count();
        $sample = $query->limit(5)->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn ($c) => ['name' => "{$c->first_name} {$c->last_name}", 'email' => $c->email])
            ->all();

        return ['count' => $total, 'sample' => $sample];
    }
}
