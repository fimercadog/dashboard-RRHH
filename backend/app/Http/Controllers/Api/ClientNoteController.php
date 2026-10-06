<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ClientNoteResource;
use App\Models\ClientNote;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ClientNoteController extends BaseCrudController
{
    protected string $model = ClientNote::class;
    protected string $resource = ClientNoteResource::class;
    protected array $filterable = ['client_id' => 'client_id'];
    protected array $with = ['user:id,name'];

    public function store(Request $request, AuditService $audit)
    {
        $payload = $this->validatedInput($request);
        $payload['company_id'] = $this->companyId($request);
        $payload['user_id'] = $request->user()->id;
        $note = ClientNote::create($payload)->load($this->with);
        $audit->record('created', $note, $request);

        return (new ClientNoteResource($note))->response()->setStatusCode(201);
    }
}
