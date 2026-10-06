<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ActivityController extends BaseCrudController
{
    protected string $model = Activity::class;
    protected string $resource = ActivityResource::class;
    protected array $searchable = ['title', 'body'];
    protected array $filterable = [
        'deal_id'    => 'deal_id',
        'client_id'  => 'client_id',
        'contact_id' => 'contact_id',
        'type'       => 'type',
        'status'     => 'status',
    ];
    protected array $with = ['user:id,name', 'deal:id,title', 'client:id,first_name,last_name,company_name'];

    public function store(Request $request, AuditService $audit)
    {
        $payload = $this->validatedInput($request);
        $payload['company_id'] = $this->companyId($request);
        $payload['user_id'] ??= $request->user()->id;
        $activity = Activity::create($payload)->load($this->with);
        $audit->record('created', $activity, $request);

        return (new ActivityResource($activity))->response()->setStatusCode(201);
    }
}
