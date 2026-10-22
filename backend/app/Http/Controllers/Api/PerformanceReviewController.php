<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PerformanceReviewResource;
use App\Models\PerformanceReview;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceReviewController extends BaseCrudController
{
    protected string $model    = PerformanceReview::class;
    protected string $resource = PerformanceReviewResource::class;
    protected array  $with     = ['employee'];
    protected array  $immutableOnUpdate = ['employee_id'];
    protected array  $searchable  = ['strengths', 'improvements'];
    protected array  $filterable  = [
        'status'      => 'status',
        'employee_id' => 'employee_id',
    ];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = PerformanceReview::where('company_id', $cid);
        return response()->json([
            'total'      => $q->clone()->count(),
            'pending'    => $q->clone()->where('status', 'draft')->count(),
            'submitted'  => $q->clone()->where('status', 'submitted')->count(),
            'avg_rating' => round((float) $q->clone()->whereNotNull('overall_rating')->avg('overall_rating'), 1),
        ]);
    }

    public function submit(Request $request, string $id, AuditService $audit): JsonResponse
    {
        $cid    = $this->companyId($request);
        $review = PerformanceReview::where('company_id', $cid)->findOrFail($id);
        $old    = $review->getOriginal();

        $review->update(['status' => 'submitted', 'submitted_at' => now()]);
        $audit->record('submitted', $review, $request, $old);
        Cache::increment("company_version_{$cid}");

        return (new PerformanceReviewResource($review->load('employee')))->response();
    }
}
