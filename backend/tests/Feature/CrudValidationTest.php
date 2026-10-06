<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CrudValidationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(['name' => 'Test SA']);
        foreach (['employees.manage', 'settings.manage'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo(['employees.manage', 'settings.manage']);
        Sanctum::actingAs($user, ['*']);
    }

    public function test_rejects_garbage_employee_payload(): void
    {
        $this->postJson('/api/employees', [
            'employee_code' => '632874',
            'first_name' => 'fidel1',
            'last_name' => 'hfadjkh1',
            'identification_type' => 'cc',
            'identification_number' => '3289675498a',
            'email' => 'hsdjkfgk@shdgjdf',
            'hire_date' => '2026-09-01',
            'employment_status' => 'active',
            'department_id' => 5216,
            'position_id' => 6273864,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name', 'last_name', 'identification_type',
                'identification_number', 'email', 'department_id', 'position_id',
            ]);
    }

    public function test_accepts_a_valid_employee(): void
    {
        $this->postJson('/api/employees', [
            'employee_code' => 'EMP-9001',
            'first_name' => 'Ana Maria',
            'last_name' => 'Perez',
            'identification_type' => 'CC',
            'identification_number' => '1032456789',
            'email' => 'ana@test.com',
            'hire_date' => '2025-06-01',
            'employment_status' => 'active',
        ])->assertCreated();
    }

    public function test_partial_update_still_works_for_status_toggle(): void
    {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP-9002',
            'first_name' => 'Luis',
            'last_name' => 'Diaz',
            'identification_type' => 'CC',
            'identification_number' => '1000000002',
            'hire_date' => '2025-01-01',
            'employment_status' => 'active',
        ]);

        $this->putJson("/api/employees/{$employee->id}", ['employment_status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.employment_status', 'inactive');
    }

    public function test_department_module_is_also_validated(): void
    {
        $this->postJson('/api/departments', ['name' => '', 'status' => 'nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status']);
    }
}
