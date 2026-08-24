<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeDocumentResource;
use App\Models\EmployeeDocument;

class EmployeeDocumentController extends BaseCrudController
{
    protected string $model = EmployeeDocument::class;
    protected string $resource = EmployeeDocumentResource::class;
    protected array $with = ['employee'];
    protected array $searchable = ['name', 'document_type'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];
}
