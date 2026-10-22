<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartOfAccountController extends BaseCrudController
{
    protected string $model    = ChartOfAccount::class;
    protected string $resource = ChartOfAccountResource::class;
    protected array  $with     = ['parent'];
    protected array  $searchable = ['code', 'name'];
    protected array  $filterable = ['type', 'status', 'allows_movements', 'parent_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = ChartOfAccount::where('company_id', $cid);
        return response()->json([
            'total'        => $q->clone()->count(),
            'active'       => $q->clone()->where('status', 'active')->count(),
            'movable'      => $q->clone()->where('status', 'active')->where('allows_movements', true)->count(),
        ]);
    }
}
