<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeWorkHistory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeWorkHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        foreach (['employees.manage', 'dashboard.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos  = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);

        $this->employee = Employee::factory()->create([
            'company_id'    => $this->company->id,
            'department_id' => $dept->id,
            'position_id'   => $pos->id,
        ]);
    }

    private function actingAsManager(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo(['employees.manage', 'dashboard.view']);
        Sanctum::actingAs($user);
    }

    private function baseUrl(): string
    {
        return "/api/employees/{$this->employee->id}/work-history";
    }

    #[Test]
    public function unauthenticated_returns_401(): void
    {
        $this->getJson($this->baseUrl())->assertStatus(401);
    }

    #[Test]
    public function without_manage_permission_returns_403(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo('dashboard.view');
        Sanctum::actingAs($user);

        $this->getJson($this->baseUrl())->assertStatus(403);
    }

    #[Test]
    public function can_list_empty_history(): void
    {
        $this->actingAsManager();

        $this->getJson($this->baseUrl())
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    #[Test]
    public function can_create_work_history_entry(): void
    {
        $this->actingAsManager();

        $response = $this->postJson($this->baseUrl(), [
            'event_type'     => 'hire',
            'title'          => 'Contratacion inicial',
            'description'    => 'Ingreso a la empresa',
            'effective_date' => '2024-01-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.event_type', 'hire')
            ->assertJsonPath('data.title', 'Contratacion inicial')
            ->assertJsonPath('data.employee_id', $this->employee->id);

        $this->assertDatabaseHas('employee_work_history', [
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'event_type'  => 'hire',
        ]);
    }

    #[Test]
    public function rejects_invalid_event_type(): void
    {
        $this->actingAsManager();

        $this->postJson($this->baseUrl(), [
            'event_type'     => 'invalid_type',
            'title'          => 'Algo',
            'effective_date' => '2024-01-15',
        ])->assertStatus(422)->assertJsonValidationErrors(['event_type']);
    }

    #[Test]
    public function can_update_entry(): void
    {
        $this->actingAsManager();

        $entry = EmployeeWorkHistory::create([
            'company_id'     => $this->company->id,
            'employee_id'    => $this->employee->id,
            'event_type'     => 'other',
            'title'          => 'Original',
            'effective_date' => '2024-03-01',
        ]);

        $this->putJson("{$this->baseUrl()}/{$entry->id}", ['title' => 'Actualizado'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Actualizado');
    }

    #[Test]
    public function can_delete_entry(): void
    {
        $this->actingAsManager();

        $entry = EmployeeWorkHistory::create([
            'company_id'     => $this->company->id,
            'employee_id'    => $this->employee->id,
            'event_type'     => 'other',
            'title'          => 'A borrar',
            'effective_date' => '2024-03-01',
        ]);

        $this->deleteJson("{$this->baseUrl()}/{$entry->id}")->assertNoContent();
        $this->assertDatabaseMissing('employee_work_history', ['id' => $entry->id]);
    }

    #[Test]
    public function cross_company_employee_returns_404(): void
    {
        $this->actingAsManager();

        $other = Company::factory()->create();
        $dept2 = Department::factory()->create(['company_id' => $other->id]);
        $pos2  = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id]);
        $foreign = Employee::factory()->create([
            'company_id'    => $other->id,
            'department_id' => $dept2->id,
            'position_id'   => $pos2->id,
        ]);

        $this->getJson("/api/employees/{$foreign->id}/work-history")->assertNotFound();
    }

    #[Test]
    public function cross_company_entry_returns_404_on_delete(): void
    {
        $this->actingAsManager();

        $other = Company::factory()->create();
        $dept2 = Department::factory()->create(['company_id' => $other->id]);
        $pos2  = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept2->id]);
        $foreign = Employee::factory()->create([
            'company_id'    => $other->id,
            'department_id' => $dept2->id,
            'position_id'   => $pos2->id,
        ]);
        $entry = EmployeeWorkHistory::create([
            'company_id'     => $other->id,
            'employee_id'    => $foreign->id,
            'event_type'     => 'other',
            'title'          => 'Otro',
            'effective_date' => '2024-01-01',
        ]);

        // Intenta borrar entrada de otra empresa usando el employee de la propia empresa en la URL
        $this->deleteJson("{$this->baseUrl()}/{$entry->id}")->assertNotFound();
    }

    #[Test]
    public function employee_show_includes_work_history(): void
    {
        $this->actingAsManager();

        EmployeeWorkHistory::create([
            'company_id'     => $this->company->id,
            'employee_id'    => $this->employee->id,
            'event_type'     => 'promotion',
            'title'          => 'Ascenso a senior',
            'effective_date' => '2025-06-01',
        ]);

        $this->getJson("/api/employees/{$this->employee->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.work_history')
            ->assertJsonPath('data.work_history.0.event_type', 'promotion');
    }

    #[Test]
    public function social_security_fields_persist_on_create_and_update(): void
    {
        $this->actingAsManager();

        $dept = Department::factory()->create(['company_id' => $this->company->id]);
        $pos  = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);

        $response = $this->postJson('/api/employees', [
            'employee_code'         => 'EMP-TEST-SS',
            'first_name'            => 'Ana',
            'last_name'             => 'Lopez',
            'identification_type'   => 'CC',
            'identification_number' => '999888777',
            'hire_date'             => '2025-01-01',
            'employment_status'     => 'active',
            'department_id'         => $dept->id,
            'position_id'           => $pos->id,
            'eps'                   => 'Sura',
            'arl'                   => 'Positiva',
            'pension_fund'          => 'Proteccion',
            'compensation_fund'     => 'Comfenalco',
        ]);

        $response->assertStatus(201);
        $id = $response->json('data.id');

        $this->getJson("/api/employees/{$id}")
            ->assertJsonPath('data.eps', 'Sura')
            ->assertJsonPath('data.arl', 'Positiva')
            ->assertJsonPath('data.pension_fund', 'Proteccion')
            ->assertJsonPath('data.compensation_fund', 'Comfenalco');
    }
}
