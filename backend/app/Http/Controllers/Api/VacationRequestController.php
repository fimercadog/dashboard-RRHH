<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\VacationRequestResource;
use App\Models\VacationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacationRequestController extends BaseCrudController
{
    protected string $model = VacationRequest::class;
    protected string $resource = VacationRequestResource::class;
    protected array $with = ['employee', 'approver'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];
    protected array $immutableOnUpdate = ['employee_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = VacationRequest::where('company_id', $cid);
        return response()->json([
            'total'          => $q->clone()->count(),
            'pending'        => $q->clone()->where('status', 'pending')->count(),
            'approved'       => $q->clone()->where('status', 'approved')->count(),
            'days_this_year' => (int) $q->clone()->where('status', 'approved')->whereYear('start_date', now()->year)->sum('requested_days'),
        ]);
    }
}
