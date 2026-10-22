<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SickLeaveResource;
use App\Models\SickLeave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SickLeaveController extends BaseCrudController
{
    protected string $model = SickLeave::class;
    protected string $resource = SickLeaveResource::class;
    protected array $with = ['employee'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];
    protected array $immutableOnUpdate = ['employee_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = SickLeave::where('company_id', $cid);
        return response()->json([
            'total'          => $q->clone()->count(),
            'active'         => $q->clone()->where('status', 'approved')->count(),
            'days_this_year' => (int) $q->clone()->whereYear('start_date', now()->year)->sum('days'),
        ]);
    }
}
