<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobCandidate;
use App\Models\JobInterview;
use App\Models\JobVacancy;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $perms = ['recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.delete', 'dashboard.view'];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'Test Admin', 'guard_name' => 'web']);
        $role->givePermissionTo($perms);

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole($role);
    }

    // --- JobVacancy ---

    public function test_admin_can_create_vacancy(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/job-vacancies', [
            'title'      => 'Dev Laravel',
            'opened_at'  => '2026-10-01',
            'status'     => 'open',
            'vacancies_count' => 1,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('job_vacancies', ['company_id' => $this->company->id, 'title' => 'Dev Laravel']);
    }

    public function test_admin_can_list_vacancies(): void
    {
        Sanctum::actingAs($this->admin);
        JobVacancy::factory()->create(['company_id' => $this->company->id]);

        $this->getJson('/api/job-vacancies')->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_delete_vacancy(): void
    {
        Sanctum::actingAs($this->admin);
        $v = JobVacancy::factory()->create(['company_id' => $this->company->id]);

        $this->deleteJson("/api/job-vacancies/{$v->id}")->assertNoContent();
        $this->assertSoftDeleted('job_vacancies', ['id' => $v->id]);
    }

    // --- JobCandidate ---

    public function test_admin_can_create_candidate(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/job-candidates', [
            'first_name' => 'Ana',
            'last_name'  => 'López',
            'email'      => 'ana@test.com',
        ])->assertStatus(201);

        $this->assertDatabaseHas('job_candidates', ['company_id' => $this->company->id, 'first_name' => 'Ana']);
    }

    // --- JobApplication ---

    public function test_admin_can_create_application(): void
    {
        Sanctum::actingAs($this->admin);

        $vacancy   = JobVacancy::factory()->create(['company_id' => $this->company->id]);
        $candidate = JobCandidate::factory()->create(['company_id' => $this->company->id]);

        $this->postJson('/api/job-applications', [
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $candidate->id,
            'applied_at'       => '2026-10-01',
            'status'           => 'new',
        ])->assertStatus(201);
    }

    // --- Hire action ---

    public function test_hire_creates_employee_and_links_application(): void
    {
        Sanctum::actingAs($this->admin);

        $dept      = Department::factory()->create(['company_id' => $this->company->id]);
        $pos       = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);
        $vacancy   = JobVacancy::factory()->create(['company_id' => $this->company->id]);
        $candidate = JobCandidate::factory()->create([
            'company_id'  => $this->company->id,
            'first_name'  => 'Pedro',
            'last_name'   => 'Ramírez',
            'email'       => 'pedro@test.com',
        ]);
        $app = JobApplication::factory()->create([
            'company_id'       => $this->company->id,
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $candidate->id,
            'status'           => 'interview',
        ]);

        $response = $this->postJson("/api/job-applications/{$app->id}/hire");

        $response->assertOk()->assertJsonStructure(['employee_id', 'employee_code']);

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'first_name' => 'Pedro',
            'last_name'  => 'Ramírez',
        ]);

        $this->assertDatabaseHas('job_applications', [
            'id'     => $app->id,
            'status' => 'selected',
        ]);
    }

    public function test_hire_prevents_double_hiring(): void
    {
        Sanctum::actingAs($this->admin);

        $dept      = Department::factory()->create(['company_id' => $this->company->id]);
        $vacancy   = JobVacancy::factory()->create(['company_id' => $this->company->id]);
        $candidate = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $employee  = Employee::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);
        $app       = JobApplication::factory()->create([
            'company_id'        => $this->company->id,
            'job_vacancy_id'    => $vacancy->id,
            'job_candidate_id'  => $candidate->id,
            'status'            => 'selected',
            'hired_employee_id' => $employee->id,
        ]);

        $this->postJson("/api/job-applications/{$app->id}/hire")->assertStatus(422);
    }

    // --- RBAC: recruitment.delete is exclusive ---

    public function test_rrhh_role_cannot_delete_vacancy(): void
    {
        $rrhh = Role::firstOrCreate(['name' => 'RRHH Test', 'guard_name' => 'web']);
        $rrhh->syncPermissions(['recruitment.view', 'recruitment.create', 'recruitment.update', 'dashboard.view']);

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole($rrhh);
        Sanctum::actingAs($user);

        $v = JobVacancy::factory()->create(['company_id' => $this->company->id]);

        $this->deleteJson("/api/job-vacancies/{$v->id}")->assertStatus(403);
    }

    // --- JobInterview ---

    public function test_admin_can_create_interview(): void
    {
        Sanctum::actingAs($this->admin);

        $vacancy   = JobVacancy::factory()->create(['company_id' => $this->company->id]);
        $candidate = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $app       = JobApplication::factory()->create([
            'company_id'       => $this->company->id,
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $candidate->id,
        ]);

        $this->postJson('/api/job-interviews', [
            'job_application_id' => $app->id,
            'scheduled_at'       => '2026-10-20 10:00:00',
            'type'               => 'virtual',
            'result'             => 'pending',
        ])->assertStatus(201);
    }
}
