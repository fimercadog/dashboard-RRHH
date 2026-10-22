<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobCandidateResource;
use App\Models\JobCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCandidateController extends BaseCrudController
{
    protected string $model = JobCandidate::class;
    protected string $resource = JobCandidateResource::class;
    protected array $with = [];
    protected array $searchable = ['first_name', 'last_name', 'email', 'identification_number'];
    protected array $filterable = [];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        return response()->json([
            'total'  => JobCandidate::where('company_id', $cid)->count(),
            'hired'  => \App\Models\JobApplication::where('company_id', $cid)->where('status', 'selected')->whereNotNull('hired_employee_id')->distinct('job_candidate_id')->count(),
        ]);
    }
}

