<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobInterviewResource;
use App\Models\JobInterview;

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
}

