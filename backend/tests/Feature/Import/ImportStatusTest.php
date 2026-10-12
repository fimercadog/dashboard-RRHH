<?php

namespace Tests\Feature\Import;

use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ImportStatusTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();
        $this->userA    = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->userB    = User::factory()->create(['company_id' => $this->companyB->id]);

        foreach (['employees.manage', 'clients.manage', 'inventory.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    public function test_returns_status_for_own_company_job(): void
    {
        $this->userA->givePermissionTo('employees.manage');
        Sanctum::actingAs($this->userA, ['*']);

        $job = ImportJob::create([
            'company_id' => $this->companyA->id,
            'user_id'    => $this->userA->id,
            'entity'     => 'employees',
            'status'     => 'completed',
            'file_path'  => 'imports/1/test.csv',
            'file_name'  => 'test.csv',
        ]);

        $this->getJson("/api/import/{$job->id}/status")
            ->assertOk()
            ->assertJsonFragment(['status' => 'completed']);
    }

    public function test_returns_404_for_other_company_job(): void
    {
        // userB tries to read a job that belongs to companyA
        Sanctum::actingAs($this->userB, ['*']);

        $job = ImportJob::create([
            'company_id' => $this->companyA->id,
            'user_id'    => $this->userA->id,
            'entity'     => 'employees',
            'status'     => 'completed',
            'file_path'  => 'imports/1/test.csv',
            'file_name'  => 'test.csv',
        ]);

        $this->getJson("/api/import/{$job->id}/status")
            ->assertNotFound();
    }
}
