<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends BaseCrudController
{
    protected string $model = User::class;
    protected string $resource = UserResource::class;
    protected array $with = ['employee'];
    protected array $searchable = ['name', 'email'];
    protected array $filterable = ['status' => 'status'];

    public function store(Request $request, AuditService $audit)
    {
        $temporaryPassword = null;
        $payload = $request->except(['password', 'role']);

        if ($request->filled('password')) {
            $payload['password'] = $request->input('password');
        } else {
            $temporaryPassword = Str::password(12);
            $payload['password'] = $temporaryPassword;
        }

        $payload['company_id'] ??= $this->companyId($request);
        $user = User::create($payload);

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        $user->load($this->with);
        $audit->record('created', $user, $request);

        $response = (new UserResource($user))->response()->setStatusCode(201);
        if ($temporaryPassword) {
            $response->setData(['data' => $response->getData()->data, 'temporary_password' => $temporaryPassword]);
        }

        return $response;
    }

    public function update(Request $request, string $id, AuditService $audit)
    {
        $user = User::query()->where('company_id', $this->companyId($request))->findOrFail($id);
        $oldValues = $user->getOriginal();

        $payload = $request->except(['password', 'role']);
        if ($request->filled('password')) {
            $payload['password'] = $request->input('password');
        }

        $user->update($payload);

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        $user->load($this->with);
        $audit->record('updated', $user, $request, $oldValues);

        return new UserResource($user);
    }
}
