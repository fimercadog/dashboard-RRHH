<?php

namespace Tests\Feature\Import;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ImportJob;
use App\Models\Position;
use App\Models\User;
use App\Services\Import\ChunkWriter;
use App\Services\Import\DuplicateChecker;
use App\Services\Import\ErrorAccumulator;
use App\Services\Import\ImportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeImportEngineTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->company = Company::factory()->create();
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);
    }

    private function engine(): ImportEngine
    {
        return new ImportEngine(
            new ErrorAccumulator,
            new DuplicateChecker,
            new ChunkWriter,
        );
    }

    private function makeJob(string $csvContent): ImportJob
    {
        $path = "imports/{$this->company->id}/test.csv";
        Storage::disk('local')->put($path, $csvContent);

        return ImportJob::create([
            'company_id' => $this->company->id,
            'user_id'    => $this->user->id,
            'entity'     => 'employees',
            'status'     => 'pending',
            'file_path'  => $path,
            'file_name'  => 'test.csv',
        ]);
    }

    public function test_duplicate_active_employee_is_skipped(): void
    {
        Employee::create([
            'company_id'            => $this->company->id,
            'employee_code'         => 'E001',
            'first_name'            => 'Existing',
            'last_name'             => 'User',
            'identification_type'   => 'CC',
            'identification_number' => '999',
            'hire_date'             => '2020-01-01',
            'employment_status'     => 'active',
        ]);

        $csv = "employee_code,first_name,last_name,identification_type,identification_number,hire_date,employment_status\n"
             . "E001,Juan,Perez,CC,12345,2024-01-01,active\n";

        $job = $this->makeJob($csv);
        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(1, $job->skipped_rows);
        $this->assertEquals(0, $job->created_rows);
    }

    public function test_soft_deleted_employee_fails_row(): void
    {
        $emp = Employee::create([
            'company_id'            => $this->company->id,
            'employee_code'         => 'E002',
            'first_name'            => 'Deleted',
            'last_name'             => 'User',
            'identification_type'   => 'CC',
            'identification_number' => '77777',
            'hire_date'             => '2020-01-01',
            'employment_status'     => 'terminated',
        ]);
        $emp->delete(); // soft delete

        $csv = "employee_code,first_name,last_name,identification_type,identification_number,hire_date,employment_status\n"
             . "E002,Juan,Perez,CC,77777,2024-01-01,active\n";

        $job = $this->makeJob($csv);
        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(1, $job->failed_rows);
        $this->assertEquals(0, $job->created_rows);
        $this->assertNotEmpty($job->errors);
    }

    public function test_invalid_position_id_fails_row(): void
    {
        $csv = "employee_code,first_name,last_name,identification_type,identification_number,hire_date,employment_status,position_id\n"
             . "E003,Juan,Perez,CC,11111,2024-01-01,active,9999\n"; // non-existent position

        $job = $this->makeJob($csv);
        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(1, $job->failed_rows);
        $this->assertEquals(0, $job->created_rows);
    }

    public function test_counters_reset_between_retries(): void
    {
        $csv = "employee_code,first_name,last_name,identification_type,identification_number,hire_date,employment_status\n"
             . "E010,Juan,Perez,CC,10101,2024-01-01,active\n";

        $job = $this->makeJob($csv);

        // ProcessImportJob resets counters before calling engine->run()
        // Simulate that reset + run cycle
        $job->update([
            'status'         => 'processing',
            'processed_rows' => 0,
            'created_rows'   => 0,
            'skipped_rows'   => 0,
            'failed_rows'    => 0,
            'errors'         => null,
            'started_at'     => now(),
            'completed_at'   => null,
        ]);

        $this->engine()->run($job);
        $job->refresh();

        // After engine->run(), counters reflect only this run
        $this->assertEquals(1, $job->processed_rows);
        $this->assertEquals(1, $job->created_rows);
        $this->assertEquals(0, $job->skipped_rows);
        $this->assertEquals(0, $job->failed_rows);
    }
}
