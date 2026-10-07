<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JobVacancyResource;
use App\Models\JobVacancy;

class JobVacancyController extends BaseCrudController
{
    protected string $model = JobVacancy::class;
    protected string $resource = JobVacancyResource::class;
    protected array $with = ['department:id,name'];
    protected array $searchable = ['title', 'description'];
    protected array $filterable = ['status', 'department_id'];
}

