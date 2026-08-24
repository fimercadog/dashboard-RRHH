<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function record(string $action, Model $model, Request $request, ?array $oldValues = null): void
    {
        AuditLog::create([
            'company_id' => $model->company_id ?? $request->user()?->company_id,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'module' => str($model::class)->classBasename()->snake('-')->toString(),
            'entity' => $model::class,
            'entity_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $model->getAttributes(),
            'ip_address' => $request->ip(),
        ]);
    }
}
