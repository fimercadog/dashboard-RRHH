<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\TableQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

abstract class BaseCrudController extends Controller
{
    use ResolvesCompany;

    protected string $model;

    protected string $resource;

    protected array $with = [];

    protected array $searchable = [];

    protected array $filterable = [];

    protected array $sortable = [];

    /** Campos que no pueden modificarse al actualizar — siempre se descartan del payload de update. */
    protected array $immutableOnUpdate = [];

    /**
     * Payload validado. Si existe App\Http\Requests\Store{Modelo}Request se
     * aplican sus reglas (mismo FormRequest para crear y actualizar; en update
     * las reglas pasan a "sometimes" porque el toggle de estado manda payload
     * parcial). Sin FormRequest, se conserva el comportamiento previo.
     */
    protected function validatedInput(Request $request, bool $isUpdate = false): array
    {
        $class = 'App\\Http\\Requests\\Store'.class_basename($this->model).'Request';

        if (! class_exists($class)) {
            return $request->all();
        }

        if (! $isUpdate) {
            return app($class)->validated();
        }

        $form = new $class;
        $rules = collect($form->rules())
            ->map(fn ($rule) => array_values(array_unique(['sometimes', ...(array) $rule])))
            ->all();

        return validator($request->all(), $rules, $form->messages(), $form->attributes())->validate();
    }

    public function index(Request $request, TableQueryService $tables)
    {
        $query = ($this->model)::query()
            ->where('company_id', $this->companyId($request))
            ->with($this->with);

        $tables->apply($request, $query, $this->searchable, $this->filterable, $this->sortable);

        return ($this->resource)::collection($query->paginate(min((int) $request->input('per_page', TableQueryService::DEFAULT_PER_PAGE), TableQueryService::MAX_PER_PAGE)));
    }

    public function store(Request $request, AuditService $audit)
    {
        $payload = $this->validatedInput($request);
        $companyId = $this->companyId($request);
        $payload['company_id'] = $companyId;
        $model = ($this->model)::create($payload)->load($this->with);
        $audit->record('created', $model, $request);
        Cache::increment("company_version_{$companyId}");

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
        $cid = $this->companyId($request);
        $model = ($this->model)::query()->where('company_id', $cid)->findOrFail($id);
        $oldValues = $model->getOriginal();
        $payload = $this->validatedInput($request, isUpdate: true);
        foreach ($this->immutableOnUpdate as $field) {
            unset($payload[$field]);
        }
        $payload['company_id'] = $cid;
        $model->update($payload);
        $model->load($this->with);
        $audit->record('updated', $model, $request, $oldValues);
        Cache::increment("company_version_{$cid}");

        return new $this->resource($model);
    }

    public function destroy(Request $request, string $id, AuditService $audit)
    {
        $cid = $this->companyId($request);
        $model = ($this->model)::query()->where('company_id', $cid)->findOrFail($id);
        $audit->record('deleted', $model, $request, $model->getOriginal());
        $model->delete();
        Cache::increment("company_version_{$cid}");

        return response()->noContent();
    }
}
