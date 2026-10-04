<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ClientController extends BaseCrudController
{
    protected string $model = Client::class;
    protected string $resource = ClientResource::class;
    protected array $searchable = ['first_name', 'last_name', 'email', 'identification_number', 'company_name'];
    protected array $filterable = ['status' => 'status'];

    public function store(Request $request, AuditService $audit)
    {
        if (! $request->filled('client_uuid')) {
            return parent::store($request, $audit);
        }

        $payload = $this->validatedInput($request);
        $payload['company_id'] ??= $this->companyId($request);
        $uuid = $payload['client_uuid'];

        $model = Client::firstOrCreate(['client_uuid' => $uuid], $payload);

        if ($model->wasRecentlyCreated) {
            $audit->record('created', $model, $request);
        }

        return (new ClientResource($model))
            ->response()
            ->setStatusCode($model->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, string $id, AuditService $audit)
    {
        $client = Client::query()->where('company_id', $this->companyId($request))->findOrFail($id);

        if ($client->patients()->exists()) {
            return response()->json(['message' => 'No se puede eliminar un cliente con pacientes activos. Desactívelo o transfiera sus pacientes primero.'], 422);
        }

        $audit->record('deleted', $client, $request, $client->getOriginal());
        $client->delete(); // SoftDelete

        return response()->noContent();
    }
}
