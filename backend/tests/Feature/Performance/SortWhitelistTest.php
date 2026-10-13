<?php

namespace Tests\Feature\Performance;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SortWhitelistTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['name' => 'Whitelist Test SA']);

        Permission::firstOrCreate(['name' => 'employees.manage', 'guard_name' => 'web']);
    }

    private function loginWith(array $permissions = []): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo($permissions);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_sort_by_valid_column_returns_200(): void
    {
        $this->loginWith(['employees.manage']);

        $this->getJson('/api/employees?sort=created_at')->assertOk();
    }

    public function test_sort_by_invalid_column_returns_200_without_500(): void
    {
        $this->loginWith(['employees.manage']);

        // 'notes' is not in the whitelist — should fall back to created_at, not 500
        $response = $this->getJson('/api/employees?sort=notes');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_date_field_valid_created_at_returns_200(): void
    {
        $this->loginWith(['employees.manage']);
        $this->getJson('/api/employees?date_from=2024-01-01&date_field=created_at')->assertOk();
    }

    public function test_date_field_invalid_column_falls_back_safely(): void
    {
        $this->loginWith(['employees.manage']);
        // 'password' no está en la lista permitida — debe ignorarse y usar created_at
        $response = $this->getJson('/api/employees?date_from=2024-01-01&date_field=password');
        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta']);
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_date_field_valid_updated_at_returns_200(): void
    {
        $this->loginWith(['employees.manage']);
        $this->getJson('/api/employees?date_from=2024-01-01&date_field=updated_at')->assertOk();
    }
}
