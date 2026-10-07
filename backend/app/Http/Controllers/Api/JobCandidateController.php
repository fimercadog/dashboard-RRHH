<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobCandidateResource;
use App\Models\JobCandidate;

class JobCandidateController extends BaseCrudController
{
    protected string $model = JobCandidate::class;
    protected string $resource = JobCandidateResource::class;
    protected array $with = [];
    protected array $searchable = ['first_name', 'last_name', 'email', 'identification_number'];
    protected array $filterable = [];
}

