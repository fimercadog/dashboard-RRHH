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
