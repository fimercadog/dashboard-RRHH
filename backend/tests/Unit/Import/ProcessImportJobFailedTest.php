<?php

namespace Tests\Unit\Import;

use App\Jobs\ProcessImportJob;
use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessImportJobFailedTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_callback_marks_import_job_as_failed(): void
    {
        $company = Company::factory()->create();
        $user    = User::factory()->create(['company_id' => $company->id]);

        $importJob = ImportJob::create([
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'entity'     => 'employees',
            'status'     => 'processing',
            'file_path'  => 'imports/1/test.csv',
            'file_name'  => 'test.csv',
        ]);

        $job = new ProcessImportJob($importJob->id);
        $job->failed(new \RuntimeException('Algo salió muy mal.'));

        $importJob->refresh();

        $this->assertEquals('failed', $importJob->status);
        $this->assertNotNull($importJob->completed_at);
    }
}
