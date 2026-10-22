<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobVacancyResource;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobVacancyController extends BaseCrudController
{
    protected string $model = JobVacancy::class;
    protected string $resource = JobVacancyResource::class;
    protected array $with = ['department:id,name'];
    protected array $searchable = ['title', 'description'];
    protected array $filterable = ['status', 'department_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $som = now()->startOfMonth();

        return response()->json([
            'open_vacancies'       => JobVacancy::where('company_id', $cid)->where('status', 'open')->count(),
            'active_applications'  => JobApplication::where('company_id', $cid)
                ->whereNotIn('status', ['hired', 'rejected'])->count(),
            'hired_this_month'     => JobApplication::where('company_id', $cid)
                ->where('status', 'hired')->where('updated_at', '>=', $som)->count(),
        ]);
    }
}

