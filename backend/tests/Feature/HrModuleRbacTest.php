<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\PermissionRequest;
use App\Models\Position;
use App\Models\Shift;
use App\Models\SickLeave;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regresión de seguridad RBAC — módulos RRHH.
 *
 * Motivación: tras el incidente 2026-10-06 (RRHH eliminó empleados via
 * employees.manage) se auditaron todos los DELETE del sistema y se detectaron
 * tres vulnerabilidades adicionales con el mismo patrón:
 *
 * - attendance.manage exponía destroy en attendances/shifts
 * - requests.approve  exponía destroy en vacation/permission/sick-leave requests
 * - documents.manage  exponía destroy en employee-documents
 *
 * Principio: manage ≠ delete, approve ≠ delete.
 *
 * Nuevos permisos granulares:
 *   attendance.delete / requests.delete / documents.delete
 * Solo Super Admin y Administrador de empresa los reciben.
 * RRHH conserva manage/approve pero NO puede eliminar ninguno de estos recursos.
 */
class HrModuleRbacTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $perms = [
            'attendance.manage', 'attendance.delete',
            'requests.approve',  'requests.delete',
            'documents.manage',  'documents.delete',
            'dashboard.view',
        ];
        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $dept           = Department::factory()->create(['company_id' => $this->company->id]);
        $pos            = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);
        $this->employee = Employee::factory()->create([
            'company_id'    => $this->company->id,
            'department_id' => $dept->id,
            'position_id'   => $pos->id,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function rrhh(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo(['attendance.manage', 'requests.approve', 'documents.manage']);
        Sanctum::actingAs($user, ['*']);
    }

    private function admin(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo([
            'attendance.manage', 'attendance.delete',
            'requests.approve',  'requests.delete',
            'documents.manage',  'documents.delete',
        ]);
        Sanctum::actingAs($user, ['*']);
    }

    private function otherCompanyAdmin(): void
    {
        $other = Company::factory()->create();
        $user  = User::factory()->create(['company_id' => $other->id]);
        $user->givePermissionTo(['attendance.delete', 'requests.delete', 'documents.delete']);
        Sanctum::actingAs($user, ['*']);
    }

    // ── R1: attendances ───────────────────────────────────────────────────────

    public function test_rrhh_cannot_delete_attendance(): void
    {
        $rec = Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->rrhh();

        $this->deleteJson("/api/attendances/{$rec->id}")->assertForbidden();
        $this->assertDatabaseHas('attendances', ['id' => $rec->id]);
    }

    public function test_admin_can_delete_attendance(): void
    {
        $rec = Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->admin();

        $this->deleteJson("/api/attendances/{$rec->id}")->assertNoContent();
        $this->assertDatabaseMissing('attendances', ['id' => $rec->id]);
    }

    public function test_cross_company_cannot_delete_attendance(): void
    {
        $rec = Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->otherCompanyAdmin();

        $this->deleteJson("/api/attendances/{$rec->id}")->assertNotFound();
        $this->assertDatabaseHas('attendances', ['id' => $rec->id]);
    }

    // ── R1: shifts ────────────────────────────────────────────────────────────

    public function test_rrhh_cannot_delete_shift(): void
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->rrhh();

        $this->deleteJson("/api/shifts/{$shift->id}")->assertForbidden();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
    }

    public function test_admin_can_delete_shift(): void
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->admin();

        $this->deleteJson("/api/shifts/{$shift->id}")->assertNoContent();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_cross_company_cannot_delete_shift(): void
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->otherCompanyAdmin();

        $this->deleteJson("/api/shifts/{$shift->id}")->assertNotFound();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
    }

    // ── R2: vacation-requests ─────────────────────────────────────────────────

    public function test_rrhh_cannot_delete_vacation_request(): void
    {
        $req = VacationRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->rrhh();

        $this->deleteJson("/api/vacation-requests/{$req->id}")->assertForbidden();
        $this->assertDatabaseHas('vacation_requests', ['id' => $req->id]);
    }

    public function test_admin_can_delete_vacation_request(): void
    {
        $req = VacationRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->admin();

        $this->deleteJson("/api/vacation-requests/{$req->id}")->assertNoContent();
        $this->assertDatabaseMissing('vacation_requests', ['id' => $req->id]);
    }

    public function test_cross_company_cannot_delete_vacation_request(): void
    {
        $req = VacationRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->otherCompanyAdmin();

        $this->deleteJson("/api/vacation-requests/{$req->id}")->assertNotFound();
        $this->assertDatabaseHas('vacation_requests', ['id' => $req->id]);
    }

    // ── R2: permission-requests ───────────────────────────────────────────────

    public function test_rrhh_cannot_delete_permission_request(): void
    {
        $req = PermissionRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->rrhh();

        $this->deleteJson("/api/permission-requests/{$req->id}")->assertForbidden();
        $this->assertDatabaseHas('permission_requests', ['id' => $req->id]);
    }

    public function test_admin_can_delete_permission_request(): void
    {
        $req = PermissionRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->admin();

        $this->deleteJson("/api/permission-requests/{$req->id}")->assertNoContent();
        $this->assertDatabaseMissing('permission_requests', ['id' => $req->id]);
    }

    public function test_cross_company_cannot_delete_permission_request(): void
    {
        $req = PermissionRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->otherCompanyAdmin();

        $this->deleteJson("/api/permission-requests/{$req->id}")->assertNotFound();
        $this->assertDatabaseHas('permission_requests', ['id' => $req->id]);
    }

    // ── R2: sick-leaves ───────────────────────────────────────────────────────

    public function test_rrhh_cannot_delete_sick_leave(): void
    {
        $sl = SickLeave::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->rrhh();

        $this->deleteJson("/api/sick-leaves/{$sl->id}")->assertForbidden();
        $this->assertDatabaseHas('sick_leaves', ['id' => $sl->id]);
    }

    public function test_admin_can_delete_sick_leave(): void
    {
        $sl = SickLeave::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->admin();

        $this->deleteJson("/api/sick-leaves/{$sl->id}")->assertNoContent();
        $this->assertDatabaseMissing('sick_leaves', ['id' => $sl->id]);
    }

    public function test_cross_company_cannot_delete_sick_leave(): void
    {
        $sl = SickLeave::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->otherCompanyAdmin();

        $this->deleteJson("/api/sick-leaves/{$sl->id}")->assertNotFound();
        $this->assertDatabaseHas('sick_leaves', ['id' => $sl->id]);
    }

    // ── R3: employee-documents ────────────────────────────────────────────────

    public function test_rrhh_cannot_delete_employee_document(): void
    {
        $doc = EmployeeDocument::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->rrhh();

        $this->deleteJson("/api/employee-documents/{$doc->id}")->assertForbidden();
        $this->assertDatabaseHas('employee_documents', ['id' => $doc->id]);
    }

    public function test_admin_can_delete_employee_document(): void
    {
        $doc = EmployeeDocument::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->admin();

        $this->deleteJson("/api/employee-documents/{$doc->id}")->assertNoContent();
        $this->assertDatabaseMissing('employee_documents', ['id' => $doc->id]);
    }

    public function test_cross_company_cannot_delete_employee_document(): void
    {
        $doc = EmployeeDocument::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->otherCompanyAdmin();

        $this->deleteJson("/api/employee-documents/{$doc->id}")->assertNotFound();
        $this->assertDatabaseHas('employee_documents', ['id' => $doc->id]);
    }
}
