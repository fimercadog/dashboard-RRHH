<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceStatsTest extends TestCase
{
    use RefreshDatabase;

    private Company    $company;
    private Employee   $employee;
    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        foreach (['attendance.manage', 'dashboard.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->dept     = Department::factory()->create(['company_id' => $this->company->id]);
        $pos            = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $this->dept->id]);
        $this->employee = Employee::factory()->create([
            'company_id'    => $this->company->id,
            'department_id' => $this->dept->id,
            'position_id'   => $pos->id,
        ]);
    }

    private function actingAsManager(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo('attendance.manage');
        Sanctum::actingAs($user);
    }

    private function url(array $params = []): string
    {
        return '/api/attendance-stats?' . http_build_query(array_merge([
            'from' => '2025-01-01',
            'to'   => '2025-01-31',
        ], $params));
    }

    /** @test */
    public function unauthenticated_returns_401(): void
    {
        $this->getJson('/api/attendance-stats?from=2025-01-01&to=2025-01-31')->assertStatus(401);
    }

    /** @test */
    public function without_manage_permission_returns_403(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo('dashboard.view');
        Sanctum::actingAs($user);

        $this->getJson($this->url())->assertStatus(403);
    }

    /** @test */
    public function missing_from_returns_422(): void
    {
        $this->actingAsManager();
        $this->getJson('/api/attendance-stats?to=2025-01-31')->assertStatus(422)->assertJsonValidationErrors(['from']);
    }

    /** @test */
    public function missing_to_returns_422(): void
    {
        $this->actingAsManager();
        $this->getJson('/api/attendance-stats?from=2025-01-01')->assertStatus(422)->assertJsonValidationErrors(['to']);
    }

    /** @test */
    public function from_after_to_returns_422(): void
    {
        $this->actingAsManager();
        $this->getJson('/api/attendance-stats?from=2025-01-31&to=2025-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    /** @test */
    public function no_records_returns_zeros(): void
    {
        $this->actingAsManager();

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('summary.absences.justified', 0)
            ->assertJsonPath('summary.absences.unjustified', 0)
            ->assertJsonPath('summary.tardiness.count', 0)
            ->assertJsonPath('summary.tardiness.total_minutes', 0)
            ->assertJsonPath('by_employee', [])
            ->assertJsonPath('by_department', []);
    }

    /** @test */
    public function correctly_classifies_justified_absences(): void
    {
        $this->actingAsManager();

        foreach (['permission', 'vacation', 'sick_leave'] as $i => $status) {
            Attendance::factory()->create([
                'company_id'  => $this->company->id,
                'employee_id' => $this->employee->id,
                'date'        => '2025-01-' . str_pad($i + 10, 2, '0', STR_PAD_LEFT),
                'status'      => $status,
                'late_minutes'=> 0,
            ]);
        }

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('summary.absences.justified', 3)
            ->assertJsonPath('summary.absences.unjustified', 0);
    }

    /** @test */
    public function correctly_counts_unjustified_absences(): void
    {
        $this->actingAsManager();

        Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'date'        => '2025-01-05',
            'status'      => 'absent',
            'late_minutes'=> 0,
        ]);

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('summary.absences.unjustified', 1)
            ->assertJsonPath('summary.absences.justified', 0);
    }

    /** @test */
    public function correctly_sums_tardiness(): void
    {
        $this->actingAsManager();

        Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'date'        => '2025-01-06',
            'status'      => 'late',
            'late_minutes'=> 15,
        ]);
        Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'date'        => '2025-01-07',
            'status'      => 'late',
            'late_minutes'=> 20,
        ]);

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('summary.tardiness.count', 2)
            ->assertJsonPath('summary.tardiness.total_minutes', 35);
    }

    /** @test */
    public function by_employee_includes_only_employees_with_records(): void
    {
        $this->actingAsManager();

        // Second employee — no records
        $pos2 = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $this->dept->id]);
        Employee::factory()->create(['company_id' => $this->company->id, 'department_id' => $this->dept->id, 'position_id' => $pos2->id]);

        Attendance::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'date'        => '2025-01-08',
            'status'      => 'absent',
            'late_minutes'=> 0,
        ]);

        $response = $this->getJson($this->url())->assertOk();
        $this->assertCount(1, $response->json('by_employee'));
        $this->assertEquals($this->employee->id, $response->json('by_employee.0.employee_id'));
    }

    /** @test */
    public function filter_by_employee_id(): void
    {
        $this->actingAsManager();

        $pos2  = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $this->dept->id]);
        $other = Employee::factory()->create(['company_id' => $this->company->id, 'department_id' => $this->dept->id, 'position_id' => $pos2->id]);

        foreach ([$this->employee->id, $other->id] as $i => $empId) {
            Attendance::factory()->create([
                'company_id'  => $this->company->id,
                'employee_id' => $empId,
                'date'        => '2025-01-' . str_pad($i + 5, 2, '0', STR_PAD_LEFT),
                'status'      => 'absent',
                'late_minutes'=> 0,
            ]);
        }

        $response = $this->getJson($this->url(['employee_id' => $this->employee->id]))->assertOk();
        $this->assertCount(1, $response->json('by_employee'));
        $this->assertEquals($this->employee->id, $response->json('by_employee.0.employee_id'));
    }

    /** @test */
    public function filter_by_department_id(): void
    {
        $this->actingAsManager();

        $dept2 = Department::factory()->create(['company_id' => $this->company->id]);
        $pos2  = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept2->id]);
        $other = Employee::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept2->id, 'position_id' => $pos2->id]);

        Attendance::factory()->create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'date' => '2025-01-05', 'status' => 'absent', 'late_minutes' => 0]);
        Attendance::factory()->create(['company_id' => $this->company->id, 'employee_id' => $other->id, 'date' => '2025-01-06', 'status' => 'absent', 'late_minutes' => 0]);

        $response = $this->getJson($this->url(['department_id' => $this->dept->id]))->assertOk();
        $this->assertCount(1, $response->json('by_employee'));
        $this->assertEquals($this->employee->id, $response->json('by_employee.0.employee_id'));
    }

    /** @test */
    public function cross_company_data_not_exposed(): void
    {
        $this->actingAsManager();

        $other = Company::factory()->create();
        $dept2 = Department::factory()->create(['company_id' => $other->id]);
        $pos2  = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id]);
        $emp2  = Employee::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id, 'position_id' => $pos2->id]);

        Attendance::factory()->create(['company_id' => $other->id, 'employee_id' => $emp2->id, 'date' => '2025-01-05', 'status' => 'absent', 'late_minutes' => 0]);

        $response = $this->getJson($this->url())->assertOk();
        $this->assertEquals(0, $response->json('summary.absences.unjustified'));
        $this->assertCount(0, $response->json('by_employee'));
    }

    /** @test */
    public function cross_company_employee_id_filter_returns_empty_not_cross_data(): void
    {
        $this->actingAsManager();

        // Empleado y asistencia de otra empresa
        $other = Company::factory()->create();
        $dept2 = Department::factory()->create(['company_id' => $other->id]);
        $pos2  = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id]);
        $emp2  = Employee::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id, 'position_id' => $pos2->id]);
        Attendance::factory()->create(['company_id' => $other->id, 'employee_id' => $emp2->id, 'date' => '2025-01-05', 'status' => 'absent', 'late_minutes' => 0]);

        // Asistencia propia para comprobar que los totales propios no se alteran
        Attendance::factory()->create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'date' => '2025-01-06', 'status' => 'late', 'late_minutes' => 10]);

        // Filtrar por el employee_id de la otra empresa — no debe revelar nada
        $response = $this->getJson($this->url(['employee_id' => $emp2->id]))->assertOk();
        $this->assertCount(0, $response->json('by_employee'));
        $this->assertEquals(0, $response->json('summary.absences.unjustified'));
        $this->assertEquals(0, $response->json('summary.tardiness.count'));
    }

    /** @test */
    public function range_of_366_days_is_accepted(): void
    {
        $this->actingAsManager();
        $this->getJson('/api/attendance-stats?from=2025-01-01&to=2026-01-01')->assertOk();
    }

    /** @test */
    public function range_of_367_days_returns_422(): void
    {
        $this->actingAsManager();
        $this->getJson('/api/attendance-stats?from=2025-01-01&to=2026-01-02')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    /** @test */
    public function cross_company_department_filter_returns_empty_not_cross_data(): void
    {
        $this->actingAsManager();

        $other = Company::factory()->create();
        $dept2 = Department::factory()->create(['company_id' => $other->id]);
        $pos2  = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id]);
        $emp2  = Employee::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id, 'position_id' => $pos2->id]);

        Attendance::factory()->create(['company_id' => $other->id, 'employee_id' => $emp2->id, 'date' => '2025-01-05', 'status' => 'absent', 'late_minutes' => 0]);

        // Request using a department from another company
        $response = $this->getJson($this->url(['department_id' => $dept2->id]))->assertOk();
        $this->assertCount(0, $response->json('by_employee'));
        $this->assertEquals(0, $response->json('summary.absences.unjustified'));
    }
}
