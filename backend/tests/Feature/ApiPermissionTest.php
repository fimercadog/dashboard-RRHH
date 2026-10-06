<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ApiPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['name' => 'Test SA']);

        foreach (['employees.manage', 'dashboard.view'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function loginWith(array $permissions = []): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo($permissions);
        // '*' cubre las abilities del token: aisla el chequeo de permisos Spatie.
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_route_without_permission_returns_403(): void
    {
        $this->loginWith([]);

        $this->getJson('/api/employees')->assertForbidden();
    }

    public function test_route_with_permission_is_allowed(): void
    {
        $this->loginWith(['employees.manage']);

        $this->getJson('/api/employees')->assertOk();
    }

    public function test_export_without_permission_returns_403(): void
    {
        $this->loginWith(['dashboard.view']);

        $this->get('/api/exports/employees.csv')->assertForbidden();
    }

    public function test_export_with_permission_is_allowed(): void
    {
        $this->loginWith(['employees.manage']);

        $this->get('/api/exports/employees.csv')->assertOk();
    }

    public function test_open_route_needs_no_permission(): void
    {
        $this->loginWith([]);

        $this->getJson('/api/auth/me')->assertOk();
    }
}
