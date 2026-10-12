<?php

namespace Tests\Feature\Import;

use App\Models\Client;
use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;
use App\Services\Import\ChunkWriter;
use App\Services\Import\DuplicateChecker;
use App\Services\Import\ErrorAccumulator;
use App\Services\Import\ImportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientImportEngineTest extends TestCase
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

    public function test_client_uuid_generated_on_each_created_row(): void
    {
        $csv = "first_name,last_name\n"
             . "Maria,Lopez\n"
             . "Carlos,Ramirez\n";

        $path = "imports/{$this->company->id}/clients.csv";
        Storage::disk('local')->put($path, $csv);

        $job = ImportJob::create([
            'company_id' => $this->company->id,
            'user_id'    => $this->user->id,
            'entity'     => 'clients',
            'status'     => 'pending',
            'file_path'  => $path,
            'file_name'  => 'clients.csv',
        ]);

        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(2, $job->created_rows);

        $clients = Client::where('company_id', $this->company->id)->get();
        $this->assertCount(2, $clients);

        // Each client must have a non-null, unique UUID
        $uuids = $clients->pluck('client_uuid');
        $this->assertTrue($uuids->every(fn ($u) => $u !== null && $u !== ''));
        $this->assertEquals(2, $uuids->unique()->count(), 'UUIDs deben ser únicos por fila');
    }
}
