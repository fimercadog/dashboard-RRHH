<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\SstIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SstPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company  = Company::factory()->create();
        $dept           = Department::factory()->create(['company_id' => $this->company->id]);
        $pos            = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $dept->id]);
        $this->employee = Employee::factory()->create([
            'company_id'    => $this->company->id,
            'department_id' => $dept->id,
            'position_id'   => $pos->id,
        ]);

        foreach (['sst.manage', 'sst.delete', 'desempeno.manage', 'desempeno.delete'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        $this->manager->givePermissionTo(['sst.manage', 'sst.delete', 'desempeno.manage', 'desempeno.delete']);
    }

    private function actAsManager(): void
    {
        Sanctum::actingAs($this->manager, ['*']);
    }

    private function actAsOtherCompany(): void
    {
        $other = Company::factory()->create();
        $user  = User::factory()->create(['company_id' => $other->id]);
        foreach (['sst.manage', 'desempeno.manage'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $user->givePermissionTo(['sst.manage', 'desempeno.manage']);
        Sanctum::actingAs($user, ['*']);
    }

    // ── SST ──────────────────────────────────────────────────────────────────

    public function test_can_create_sst_incident(): void
    {
        $this->actAsManager();

        $this->postJson('/api/sst/incidents', [
            'employee_id'   => $this->employee->id,
            'incident_date' => now()->toDateString(),
            'type'          => 'incident',
            'severity'      => 'low',
            'description'   => 'Caída en pasillo.',
        ])->assertCreated()->assertJsonPath('data.status', 'reported');
    }

    public function test_sst_incident_requires_description(): void
    {
        $this->actAsManager();

        $this->postJson('/api/sst/incidents', [
            'incident_date' => now()->toDateString(),
            'type'          => 'incident',
            'severity'      => 'low',
        ])->assertUnprocessable()->assertJsonValidationErrors(['description']);
    }

    public function test_sst_incident_rejects_future_date(): void
    {
        $this->actAsManager();

        $this->postJson('/api/sst/incidents', [
            'incident_date' => now()->addDays(5)->toDateString(),
            'type'          => 'incident',
            'severity'      => 'low',
            'description'   => 'Futuro.',
        ])->assertUnprocessable()->assertJsonValidationErrors(['incident_date']);
    }

    public function test_can_close_sst_incident(): void
    {
        $this->actAsManager();
        $incident = SstIncident::factory()->create([
            'company_id' => $this->company->id,
            'status'     => 'reported',
        ]);

        $this->postJson("/api/sst/incidents/{$incident->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertNotNull($incident->fresh()->resolved_at);
    }

    public function test_sst_cannot_access_other_company_incident(): void
    {
        $this->actAsManager();
        $other   = Company::factory()->create();
        $foreign = SstIncident::factory()->create(['company_id' => $other->id]);

        $this->postJson("/api/sst/incidents/{$foreign->id}/close")->assertNotFound();
    }

    public function test_sst_other_company_cannot_list_incidents(): void
    {
        SstIncident::factory()->create(['company_id' => $this->company->id]);
        $this->actAsOtherCompany();

        $res = $this->getJson('/api/sst/incidents')->assertOk();
        $this->assertCount(0, $res->json('data'));
    }

    // ── Desempeño ─────────────────────────────────────────────────────────────

    public function test_can_create_performance_review(): void
    {
        $this->actAsManager();

        $this->postJson('/api/performance-reviews', [
            'employee_id'  => $this->employee->id,
            'period_start' => '2026-01-01',
            'period_end'   => '2026-03-31',
        ])->assertCreated()->assertJsonPath('data.status', 'draft');
    }

    public function test_performance_review_requires_employee(): void
    {
        $this->actAsManager();

        $this->postJson('/api/performance-reviews', [
            'period_start' => '2026-01-01',
            'period_end'   => '2026-03-31',
        ])->assertUnprocessable()->assertJsonValidationErrors(['employee_id']);
    }

    public function test_performance_review_period_end_must_be_after_start(): void
    {
        $this->actAsManager();

        $this->postJson('/api/performance-reviews', [
            'employee_id'  => $this->employee->id,
            'period_start' => '2026-03-31',
            'period_end'   => '2026-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['period_end']);
    }

    public function test_can_submit_performance_review(): void
    {
        $this->actAsManager();
        $review = PerformanceReview::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'status'      => 'draft',
        ]);

        $this->postJson("/api/performance-reviews/{$review->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_performance_review_rating_must_be_1_to_5(): void
    {
        $this->actAsManager();
        $review = PerformanceReview::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->putJson("/api/performance-reviews/{$review->id}", [
            'overall_rating' => 6,
        ])->assertUnprocessable()->assertJsonValidationErrors(['overall_rating']);
    }

    public function test_performance_review_other_company_isolation(): void
    {
        $this->actAsManager();
        $other  = Company::factory()->create();
        $dept   = Department::factory()->create(['company_id' => $other->id]);
        $pos    = Position::factory()->create(['company_id' => $other->id, 'department_id' => $dept->id]);
        $emp    = Employee::factory()->create(['company_id' => $other->id, 'department_id' => $dept->id, 'position_id' => $pos->id]);
        $review = PerformanceReview::factory()->create(['company_id' => $other->id, 'employee_id' => $emp->id]);

        $this->postJson("/api/performance-reviews/{$review->id}/submit")->assertNotFound();
    }
}
