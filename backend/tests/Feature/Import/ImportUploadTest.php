<?php

namespace Tests\Feature\Import;

use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ImportUploadTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->company = Company::factory()->create();
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);

        foreach (['employees.manage', 'clients.manage', 'inventory.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    private function csvFile(string $contents = "employee_code,first_name,last_name,identification_type,identification_number,hire_date,employment_status\nE001,Juan,Perez,CC,12345,2024-01-01,active\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('employees.csv', $contents);
    }

    public function test_upload_employees_csv_returns_202(): void
    {
        $this->user->givePermissionTo('employees.manage');
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/import/employees', [
            'file' => $this->csvFile(),
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['import_job_id', 'status'])
            ->assertJsonFragment(['status' => 'pending']);

        $this->assertDatabaseHas('import_jobs', [
            'company_id' => $this->company->id,
            'entity'     => 'employees',
            'status'     => 'pending',
        ]);
    }

    public function test_returns_409_when_processing_job_exists(): void
    {
        $this->user->givePermissionTo('employees.manage');
        Sanctum::actingAs($this->user, ['*']);

        ImportJob::create([
            'company_id' => $this->company->id,
            'user_id'    => $this->user->id,
            'entity'     => 'employees',
            'status'     => 'processing',
            'file_path'  => 'imports/1/test.csv',
            'file_name'  => 'test.csv',
        ]);

        $response = $this->postJson('/api/import/employees', [
            'file' => $this->csvFile(),
        ]);

        $response->assertStatus(409);
    }

    public function test_returns_422_for_non_csv_file(): void
    {
        $this->user->givePermissionTo('employees.manage');
        Sanctum::actingAs($this->user, ['*']);

        $file = UploadedFile::fake()->create('employees.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->postJson('/api/import/employees', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_403_for_unauthorized_user(): void
    {
        // User has no permissions
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/import/employees', [
            'file' => $this->csvFile(),
        ]);

        $response->assertStatus(403);
    }
}
