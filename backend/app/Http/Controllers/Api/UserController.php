<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TableQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends BaseCrudController
{
    protected string $model = User::class;
    protected string $resource = UserResource::class;
    protected array $with = ['employee'];
    protected array $searchable = ['name', 'email'];
    protected array $filterable = ['status' => 'status'];

    public function index(Request $request, TableQueryService $tables)
    {
        $query = User::query()
            ->where('company_id', $this->companyId($request))
            ->with($this->with);

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->input('role')));
        }

        $tables->apply($request, $query, $this->searchable, $this->filterable);

        return UserResource::collection($query->paginate(min((int) $request->input('per_page', 10), 100)));
    }

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

        if ($request->hasFile('avatar')) {
            $cid = $user->company_id;
            $user->update(['avatar_path' => $request->file('avatar')->store("avatars/{$cid}", 'public')]);
        }

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

    public function uploadAvatar(Request $request, string $id)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = User::query()->where('company_id', $this->companyId($request))->findOrFail($id);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store("avatars/{$user->company_id}", 'public');
        $user->update(['avatar_path' => $path]);

        return response()->json([
            'avatar_url' => Storage::disk('public')->url($path),
        ]);
    }

    public function deleteAvatar(Request $request, string $id)
    {
        $user = User::query()->where('company_id', $this->companyId($request))->findOrFail($id);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return response()->json(['avatar_url' => null]);
    }
}
