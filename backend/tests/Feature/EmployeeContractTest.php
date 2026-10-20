<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Position;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeContractTest extends TestCase
{
    use RefreshDatabase;

    private Company  $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        foreach (['payroll.manage', 'payroll.view'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos  = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);
        $this->employee = Employee::factory()->create([
            'company_id'    => $this->company->id,
            'department_id' => $dept->id,
            'position_id'   => $pos->id,
        ]);
    }

    private function actingAsManager(): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo('payroll.manage');
        Sanctum::actingAs($user);
        return $user;
    }

    // ── CRUD básico ──────────────────────────────────────────────────────────

    public function test_can_create_contract(): void
    {
        $this->actingAsManager();

        $res = $this->postJson('/api/employee-contracts', [
            'employee_id'         => $this->employee->id,
            'contract_number'     => 'CT-001',
            'contract_type'       => 'indefinido',
            'salary_modality'     => 'ordinary',
            'start_date'          => '2026-01-01',
            'salary'              => 2000000,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => 1,
            'status'              => 'active',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.contract_number', 'CT-001')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_list_contracts_scoped_to_company(): void
    {
        $this->actingAsManager();

        EmployeeContract::create([
            'company_id'          => $this->company->id,
            'employee_id'         => $this->employee->id,
            'contract_number'     => 'CT-A',
            'contract_type'       => 'indefinido',
            'salary_modality'     => 'ordinary',
            'start_date'          => '2026-01-01',
            'salary'              => 2000000,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => 1,
            'status'              => 'active',
        ]);

        // Otro contrato de otra empresa
        $other    = Company::factory()->create();
        $dept     = Department::factory()->create(['company_id' => $other->id]);
        $pos      = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept->id]);
        $otherEmp = Employee::factory()->create(['company_id' => $other->id, 'department_id' => $dept->id, 'position_id' => $pos->id]);
        EmployeeContract::create([
            'company_id'          => $other->id,
            'employee_id'         => $otherEmp->id,
            'contract_number'     => 'CT-OTHER',
            'contract_type'       => 'indefinido',
            'salary_modality'     => 'ordinary',
            'start_date'          => '2026-01-01',
            'salary'              => 2000000,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => 1,
            'status'              => 'active',
        ]);

        $res = $this->getJson('/api/employee-contracts');

        $res->assertOk();
        $numbers = collect($res->json('data'))->pluck('contract_number');
        $this->assertTrue($numbers->contains('CT-A'));
        $this->assertFalse($numbers->contains('CT-OTHER'));
    }

    // ── Aislamiento multiempresa ─────────────────────────────────────────────

    public function test_cannot_access_contract_from_other_company(): void
    {
        $this->actingAsManager();

        $other    = Company::factory()->create();
        $dept2    = Department::factory()->create(['company_id' => $other->id]);
        $pos2     = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id]);
        $otherEmp = Employee::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id, 'position_id' => $pos2->id]);
        $contract = EmployeeContract::create([
            'company_id'          => $other->id,
            'employee_id'         => $otherEmp->id,
            'contract_number'     => 'FOREIGN',
            'contract_type'       => 'indefinido',
            'salary_modality'     => 'ordinary',
            'start_date'          => '2026-01-01',
            'salary'              => 2000000,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => 1,
            'status'              => 'active',
        ]);

        $this->getJson("/api/employee-contracts/{$contract->id}")
            ->assertNotFound();
    }

    // ── Sin permiso ──────────────────────────────────────────────────────────

    public function test_requires_payroll_manage_permission(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        Sanctum::actingAs($user); // sin payroll.manage

        $this->postJson('/api/employee-contracts', [])->assertForbidden();
    }

    // ── No dos contratos activos para el mismo empleado en el mismo período ──

    public function test_cannot_create_duplicate_active_contract(): void
    {
        $this->actingAsManager();

        $payload = [
            'employee_id'         => $this->employee->id,
            'contract_number'     => 'CT-001',
            'contract_type'       => 'indefinido',
            'salary_modality'     => 'ordinary',
            'start_date'          => '2026-01-01',
            'salary'              => 2000000,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => 1,
            'status'              => 'active',
        ];

        $this->postJson('/api/employee-contracts', $payload)->assertStatus(201);

        // Mismo contract_number — debe fallar por unique constraint
        $this->postJson('/api/employee-contracts', $payload)->assertStatus(422);
    }
}
