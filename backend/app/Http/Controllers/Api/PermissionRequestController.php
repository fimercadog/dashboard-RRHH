<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PermissionRequestResource;
use App\Models\PermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionRequestController extends BaseCrudController
{
    protected string $model = PermissionRequest::class;
    protected string $resource = PermissionRequestResource::class;
    protected array $with = ['employee', 'approver'];
    protected array $filterable = ['status' => 'status', 'type' => 'type', 'employee_id' => 'employee_id'];
    protected array $immutableOnUpdate = ['employee_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = PermissionRequest::where('company_id', $cid);
        return response()->json([
            'total'                => $q->clone()->count(),
            'pending'              => $q->clone()->where('status', 'pending')->count(),
            'approved_this_month'  => $q->clone()->where('status', 'approved')->whereMonth('approved_at', now()->month)->whereYear('approved_at', now()->year)->count(),
        ]);
    }
}
