<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobCandidate;
use App\Models\JobInterview;
use App\Models\JobVacancy;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Verifica que los campos de titularidad (employee_id, job_vacancy_id, etc.)
 * no pueden modificarse mediante PUT/PATCH una vez creado el registro.
 * El servidor debe conservar el valor original aunque el cliente envíe otro.
 */
class ImmutableOwnershipFieldsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();

        foreach (['requests.approve', 'recruitment.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->givePermissionTo(['requests.approve', 'recruitment.view']);
        Sanctum::actingAs($this->user, ['*']);
    }

    private function emp(): Employee
    {
        return Employee::factory()->create(['company_id' => $this->company->id]);
    }

    // ── VacationRequest ───────────────────────────────────────────────────────

    public function test_vacation_request_employee_id_cannot_change_on_update(): void
    {
        $ownerEmp = $this->emp();
        $otherEmp = $this->emp();

        $vacation = VacationRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $ownerEmp->id,
        ]);

        $this->putJson("/api/vacation-requests/{$vacation->id}", [
            'employee_id'    => $otherEmp->id,
            'start_date'     => $vacation->start_date,
            'end_date'       => $vacation->end_date,
            'requested_days' => $vacation->requested_days,
            'status'         => 'approved',
        ])->assertOk();

        $this->assertDatabaseHas('vacation_requests', [
            'id'          => $vacation->id,
            'employee_id' => $ownerEmp->id,
            'status'      => 'approved',
        ]);
        $this->assertDatabaseMissing('vacation_requests', [
            'id'          => $vacation->id,
            'employee_id' => $otherEmp->id,
        ]);
    }

    // ── JobApplication ────────────────────────────────────────────────────────

    public function test_job_application_vacancy_cannot_change_on_update(): void
    {
        $candidate    = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $origVacancy  = JobVacancy::factory()->create(['company_id' => $this->company->id]);
        $otherVacancy = JobVacancy::factory()->create(['company_id' => $this->company->id]);

        $application = JobApplication::factory()->create([
            'company_id'       => $this->company->id,
            'job_vacancy_id'   => $origVacancy->id,
            'job_candidate_id' => $candidate->id,
        ]);

        $this->putJson("/api/job-applications/{$application->id}", [
            'job_vacancy_id'   => $otherVacancy->id,
            'job_candidate_id' => $candidate->id,
            'applied_at'       => $application->applied_at,
            'status'           => 'reviewing',
        ])->assertOk();

        $this->assertDatabaseHas('job_applications', [
            'id'             => $application->id,
            'job_vacancy_id' => $origVacancy->id,
            'status'         => 'reviewing',
        ]);
    }

    public function test_job_application_candidate_cannot_change_on_update(): void
    {
        $origCandidate  = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $otherCandidate = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $vacancy        = JobVacancy::factory()->create(['company_id' => $this->company->id]);

        $application = JobApplication::factory()->create([
            'company_id'       => $this->company->id,
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $origCandidate->id,
        ]);

        $this->putJson("/api/job-applications/{$application->id}", [
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $otherCandidate->id,
            'applied_at'       => $application->applied_at,
            'status'           => 'reviewing',
        ])->assertOk();

        $this->assertDatabaseHas('job_applications', [
            'id'               => $application->id,
            'job_candidate_id' => $origCandidate->id,
        ]);
    }

    // ── JobInterview ──────────────────────────────────────────────────────────

    public function test_job_interview_application_cannot_change_on_update(): void
    {
        $candidateA  = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $candidateB  = JobCandidate::factory()->create(['company_id' => $this->company->id]);
        $vacancy     = JobVacancy::factory()->create(['company_id' => $this->company->id]);
        $origApp     = JobApplication::factory()->create([
            'company_id'       => $this->company->id,
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $candidateA->id,
        ]);
        $otherApp    = JobApplication::factory()->create([
            'company_id'       => $this->company->id,
            'job_vacancy_id'   => $vacancy->id,
            'job_candidate_id' => $candidateB->id,
        ]);

        $interview = JobInterview::factory()->create([
            'company_id'         => $this->company->id,
            'job_application_id' => $origApp->id,
        ]);

        $this->putJson("/api/job-interviews/{$interview->id}", [
            'job_application_id' => $otherApp->id,
            'scheduled_at'       => now()->addDays(2)->format('Y-m-d H:i:s'),
            'type'               => 'virtual',
            'result'             => 'pending',
        ])->assertOk();

        $this->assertDatabaseHas('job_interviews', [
            'id'                 => $interview->id,
            'job_application_id' => $origApp->id,
        ]);
    }
}
