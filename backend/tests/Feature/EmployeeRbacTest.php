<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regresión de seguridad: RRHH no debe poder eliminar empleados.
 * Motivación: incidente producción 2026-10-06 — RRHH ejecutó DELETE /employees/1
 * con employees.manage, eliminando a Camila Rojas (EMP-0001) de producción.
 */
class EmployeeRbacTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        foreach (['employees.manage', 'employees.delete', 'dashboard.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos  = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);

        $this->employee = Employee::factory()->create([
            'company_id'  => $this->company->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);
    }

    private function actingWithPermissions(array $perms): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo($perms);
        Sanctum::actingAs($user, ['*']);
    }

    // ── RRHH: employees.manage SIN employees.delete ──────────────────────────

    public function test_rrhh_cannot_delete_employee(): void
    {
        $this->actingWithPermissions(['employees.manage']);

        $this->deleteJson("/api/employees/{$this->employee->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('employees', ['id' => $this->employee->id]);
    }

    public function test_rrhh_can_list_employees(): void
    {
        $this->actingWithPermissions(['employees.manage']);

        $this->getJson('/api/employees')->assertOk();
    }

    public function test_rrhh_can_view_employee(): void
    {
        $this->actingWithPermissions(['employees.manage']);

        $this->getJson("/api/employees/{$this->employee->id}")->assertOk();
    }

    // ── Admin: employees.delete ───────────────────────────────────────────────

    public function test_admin_can_delete_employee(): void
    {
        $this->actingWithPermissions(['employees.manage', 'employees.delete']);

        $this->deleteJson("/api/employees/{$this->employee->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('employees', ['id' => $this->employee->id]);
    }

    // ── Sin permisos: 403 en todos los verbos ─────────────────────────────────

    public function test_no_permission_blocks_delete(): void
    {
        $this->actingWithPermissions([]);

        $this->deleteJson("/api/employees/{$this->employee->id}")
            ->assertForbidden();
    }

    public function test_no_permission_blocks_list(): void
    {
        $this->actingWithPermissions([]);

        $this->getJson('/api/employees')->assertForbidden();
    }

    // ── IDOR: usuario de otra empresa no puede eliminar ───────────────────────

    public function test_cross_company_delete_returns_404(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser    = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherUser->givePermissionTo(['employees.manage', 'employees.delete']);
        Sanctum::actingAs($otherUser, ['*']);

        // El empleado pertenece a $this->company, no a $otherCompany.
        $this->deleteJson("/api/employees/{$this->employee->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('employees', ['id' => $this->employee->id]);
    }
}
