<?php

namespace Tests\Feature\Import;

use App\Models\Category;
use App\Models\Company;
use App\Models\ImportJob;
use App\Models\Product;
use App\Models\User;
use App\Services\Import\ChunkWriter;
use App\Services\Import\DuplicateChecker;
use App\Services\Import\ErrorAccumulator;
use App\Services\Import\ImportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImportEngineTest extends TestCase
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
        $path = "imports/{$this->company->id}/products.csv";
        Storage::disk('local')->put($path, $csvContent);

        return ImportJob::create([
            'company_id' => $this->company->id,
            'user_id'    => $this->user->id,
            'entity'     => 'products',
            'status'     => 'pending',
            'file_path'  => $path,
            'file_name'  => 'products.csv',
        ]);
    }

    public function test_category_name_resolved_to_id(): void
    {
        $category = Category::create([
            'company_id' => $this->company->id,
            'name'       => 'Electrónica',
        ]);

        $csv = "name,category\n"
             . "Laptop,Electrónica\n";

        $job = $this->makeJob($csv);
        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(1, $job->created_rows);

        $product = Product::where('company_id', $this->company->id)->first();
        $this->assertNotNull($product);
        $this->assertEquals($category->id, $product->category_id);
    }

    public function test_ambiguous_category_fails_row(): void
    {
        Category::create(['company_id' => $this->company->id, 'name' => 'Ropa']);
        Category::create(['company_id' => $this->company->id, 'name' => 'Ropa']);

        $csv = "name,category\n"
             . "Camiseta,Ropa\n";

        $job = $this->makeJob($csv);
        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(1, $job->failed_rows);
        $this->assertEquals(0, $job->created_rows);
        $this->assertNotEmpty($job->errors);
    }

    public function test_category_not_found_fails_row(): void
    {
        $csv = "name,category\n"
             . "Mesa,Inexistente\n";

        $job = $this->makeJob($csv);
        $this->engine()->run($job);
        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertEquals(1, $job->failed_rows);
        $this->assertEquals(0, $job->created_rows);
        $this->assertNotEmpty($job->errors);
    }
}
