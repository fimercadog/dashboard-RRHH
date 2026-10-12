<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * M1 — Verifica que BaseCrudController::store() impone el company_id del usuario
 * autenticado y nunca acepta el company_id enviado en el payload.
 *
 * Se usa JobVacancyController porque no tiene StoreJobVacancyRequest; es el caso
 * más expuesto al bug (caía en $request->all() → ??= no sobreescribía).
 */
class CompanyIsolationStoreTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Company $other;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'recruitment.view', 'guard_name' => 'web']);

        $this->company = Company::factory()->create(['name' => 'Empresa A']);
        $this->other   = Company::factory()->create(['name' => 'Empresa B']);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->givePermissionTo('recruitment.view');

        Sanctum::actingAs($this->user, ['*']);
    }

    /** Sin company_id en el payload el registro queda en la empresa del usuario. */
    public function test_store_assigns_authenticated_company_id_when_omitted(): void
    {
        $this->postJson('/api/job-vacancies', [
            'title'           => 'Dev Backend',
            'opened_at'       => '2026-10-01',
            'status'          => 'open',
            'vacancies_count' => 1,
        ])->assertStatus(201);

        $this->assertDatabaseHas('job_vacancies', [
            'company_id' => $this->company->id,
            'title'      => 'Dev Backend',
        ]);
    }

    /** Con company_id de otra empresa en el payload el registro queda en la empresa del usuario (no en la enviada). */
    public function test_store_ignores_company_id_from_payload(): void
    {
        $this->postJson('/api/job-vacancies', [
            'title'           => 'Dev Frontend',
            'opened_at'       => '2026-10-01',
            'status'          => 'open',
            'vacancies_count' => 1,
            'company_id'      => $this->other->id,   // intento de inyección
        ])->assertStatus(201);

        $this->assertDatabaseHas('job_vacancies', [
            'company_id' => $this->company->id,  // debe quedar en empresa A
            'title'      => 'Dev Frontend',
        ]);

        $this->assertDatabaseMissing('job_vacancies', [
            'company_id' => $this->other->id,    // nunca debe llegar a empresa B
            'title'      => 'Dev Frontend',
        ]);
    }

    /** update() también ignora el company_id del payload — no permite mover un registro a otra empresa. */
    public function test_update_ignores_company_id_from_payload(): void
    {
        $vacancy = JobVacancy::factory()->create([
            'company_id' => $this->company->id,
            'title'      => 'Original',
            'status'     => 'open',
        ]);

        $this->patchJson("/api/job-vacancies/{$vacancy->id}", [
            'title'      => 'Actualizado',
            'company_id' => $this->other->id,  // intento de reasignación
        ])->assertOk();

        $this->assertDatabaseHas('job_vacancies', [
            'id'         => $vacancy->id,
            'company_id' => $this->company->id,  // debe permanecer en empresa A
            'title'      => 'Actualizado',
        ]);

        $this->assertDatabaseMissing('job_vacancies', [
            'id'         => $vacancy->id,
            'company_id' => $this->other->id,    // nunca debe moverse a empresa B
        ]);
    }

    /** Un FormRequest también es protegido: aunque 'company_id' no esté en sus reglas, el store lo sobreescribe. */
    public function test_store_with_form_request_also_ignores_payload_company_id(): void
    {
        // SupplierController sí tiene StoreSupplierRequest. Cubre el caso de que
        // el FormRequest filtre company_id pero igualmente el store lo impone.
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);
        $this->user->givePermissionTo('purchases.manage');

        $this->postJson('/api/suppliers', [
            'name'       => 'Proveedor Test',
            'status'     => 'active',
            'company_id' => $this->other->id,  // intento de inyección
        ])->assertStatus(201);

        $this->assertDatabaseHas('suppliers', [
            'company_id' => $this->company->id,
            'name'       => 'Proveedor Test',
        ]);

        $this->assertDatabaseMissing('suppliers', [
            'company_id' => $this->other->id,
            'name'       => 'Proveedor Test',
        ]);
    }
}
