<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Services\AuditService;
use App\Services\TableQueryService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request, TableQueryService $tables)
    {
        $query = Role::query()->with('permissions');
        $tables->apply($request, $query, ['name'], ['status' => 'status']);

        return RoleResource::collection($query->paginate(min((int) $request->input('per_page', 10), 100)));
    }

    public function store(Request $request, AuditService $audit)
    {
        $role = Role::create([
            'name' => $request->input('name'),
            'guard_name' => 'web',
            'status' => $request->input('status', 'active'),
        ]);
        $audit->record('created', $role, $request);

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $id, AuditService $audit)
    {
        $role = Role::findOrFail($id);
        $oldValues = $role->getOriginal();
        $role->update($request->only(['name', 'status']));
        $audit->record('updated', $role, $request, $oldValues);

        return new RoleResource($role);
    }
}
