<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Services\AuditService;
use App\Services\TableQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ResolvesCompany;

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        return response()->json([
            'total'  => Role::where('company_id', $cid)->count(),
            'active' => Role::where('company_id', $cid)->where('status', 'active')->count(),
        ]);
    }

    public function index(Request $request, TableQueryService $tables)
    {
        $query = Role::query()->where('company_id', $this->companyId($request))->with('permissions');
        $tables->apply($request, $query, ['name'], ['status' => 'status']);

        return RoleResource::collection($query->paginate(min((int) $request->input('per_page', 10), 100)));
    }

    public function store(Request $request, AuditService $audit)
    {
        $cid = $this->companyId($request);

        // Spatie's Role::create() checks uniqueness by (name, guard_name) globally,
        // which conflicts with per-company roles. Build the model directly instead.
        $role = new Role();
        $role->name       = $request->input('name');
        $role->guard_name = 'web';
        $role->status     = $request->input('status', 'active');
        $role->company_id = $cid;
        $role->save();

        $audit->record('created', $role, $request);

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $id, AuditService $audit)
    {
        $role = Role::where('company_id', $this->companyId($request))->findOrFail($id);
        $oldValues = $role->getOriginal();
        $role->update($request->only(['name', 'status']));
        $audit->record('updated', $role, $request, $oldValues);

        return new RoleResource($role);
    }
}
