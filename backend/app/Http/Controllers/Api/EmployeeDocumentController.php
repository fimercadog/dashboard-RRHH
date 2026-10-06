<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Http\Resources\EmployeeDocumentResource;
use App\Models\EmployeeDocument;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends BaseCrudController
{
    protected string $model = EmployeeDocument::class;
    protected string $resource = EmployeeDocumentResource::class;
    protected array $with = ['employee'];
    protected array $searchable = ['name', 'document_type'];
    protected array $filterable = ['status' => 'status', 'employee_id' => 'employee_id'];

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $cid  = $this->companyId($request);
        $data = app(StoreEmployeeDocumentRequest::class)->validated();

        $path = $request->file('file')->store("documents/{$cid}", 'public');

        $doc = EmployeeDocument::create([
            ...$data,
            'company_id' => $cid,
            'file_path'  => $path,
        ]);

        $audit->record('employee_document_created', $doc, $request);

        return (new EmployeeDocumentResource($doc->load($this->with)))->response()->setStatusCode(201);
    }
}
