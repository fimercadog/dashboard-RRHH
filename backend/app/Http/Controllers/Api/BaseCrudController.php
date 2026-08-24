<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\TableQueryService;
use Illuminate\Http\Request;

abstract class BaseCrudController extends Controller
{
    use ResolvesCompany;

    protected string $model;
    protected string $resource;
    protected array $with = [];
    protected array $searchable = [];
    protected array $filterable = [];

    public function index(Request $request, TableQueryService $tables)
    {
        $query = ($this->model)::query()
            ->where('company_id', $this->companyId($request))
            ->with($this->with);

        $tables->apply($request, $query, $this->searchable, $this->filterable);

        return ($this->resource)::collection($query->paginate(min((int) $request->input('per_page', 10), 100)));
    }

    public function store(Request $request, AuditService $audit)
    {
        $payload = $request->all();
        $payload['company_id'] ??= $this->companyId($request);
        $model = ($this->model)::create($payload)->load($this->with);
        $audit->record('created', $model, $request);

        return (new $this->resource($model))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $id)
    {
        $model = ($this->model)::query()
            ->where('company_id', $this->companyId($request))
            ->with($this->with)
            ->findOrFail($id);

        return new $this->resource($model);
    }

    public function update(Request $request, string $id, AuditService $audit)
    {
        $model = ($this->model)::query()->where('company_id', $this->companyId($request))->findOrFail($id);
        $oldValues = $model->getOriginal();
        $model->update($request->all());
        $model->load($this->with);
        $audit->record('updated', $model, $request, $oldValues);

        return new $this->resource($model);
    }

    public function destroy(Request $request, string $id, AuditService $audit)
    {
        $model = ($this->model)::query()->where('company_id', $this->companyId($request))->findOrFail($id);
        $audit->record('deleted', $model, $request, $model->getOriginal());
        $model->delete();

        return response()->noContent();
    }
}
