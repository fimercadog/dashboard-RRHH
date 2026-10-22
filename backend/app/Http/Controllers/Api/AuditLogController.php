<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseCrudController
{
    protected string $model = AuditLog::class;
    protected string $resource = AuditLogResource::class;
    protected array $with = ['user:id,name,email'];
    protected array $searchable = ['action', 'module', 'entity'];
    protected array $filterable = ['action' => 'action', 'module' => 'module', 'user_id' => 'user_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = AuditLog::where('company_id', $cid);
        return response()->json([
            'total' => $q->count(),
            'today' => $q->clone()->whereDate('created_at', today())->count(),
        ]);
    }
}
