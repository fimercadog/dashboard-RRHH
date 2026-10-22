<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobInterviewResource;
use App\Models\JobInterview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobInterviewController extends BaseCrudController
{
    protected string $model = JobInterview::class;
    protected string $resource = JobInterviewResource::class;
    protected array $with = [
        'application.candidate:id,first_name,last_name',
        'application.vacancy:id,title',
        'interviewer:id,name',
    ];
    protected array $searchable = ['notes'];
    protected array $filterable = ['type', 'result', 'job_application_id'];
    protected array $immutableOnUpdate = ['job_application_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid   = $this->companyId($request);
        $q     = JobInterview::where('company_id', $cid);
        $start = now()->startOfWeek()->toDateTimeString();
        $end   = now()->endOfWeek()->toDateTimeString();
        return response()->json([
            'total'      => $q->clone()->count(),
            'pending'    => $q->clone()->whereNull('result')->count(),
            'completed'  => $q->clone()->whereNotNull('result')->count(),
            'this_week'  => $q->clone()->whereBetween('scheduled_at', [$start, $end])->count(),
        ]);
    }
}

