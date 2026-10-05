<?php

namespace App\Communications\Sources;

use App\Contracts\AudienceSource;
use App\Models\Employee;

class ErpEmployeeSource implements AudienceSource
{
    public function name(): string { return 'Empleados del ERP'; }
    public function key(): string  { return 'erp_employees'; }
    public function isReady(): bool { return true; }
    public function notReadyMessage(): string { return ''; }

    public function preview(int $companyId, array $filters = []): array
    {
        $query = Employee::where('company_id', $companyId)
            ->where('employment_status', 'active');

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (! empty($filters['position_id'])) {
            $query->where('position_id', $filters['position_id']);
        }

        $total = $query->count();
        $sample = $query->limit(5)->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn ($e) => ['name' => "{$e->first_name} {$e->last_name}", 'email' => $e->email])
            ->all();

        return ['count' => $total, 'sample' => $sample];
    }
}
