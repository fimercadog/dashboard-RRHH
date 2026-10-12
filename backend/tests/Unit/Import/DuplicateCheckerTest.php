<?php

namespace Tests\Unit\Import;

use App\Contracts\ImportableEntity;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Import\DuplicateChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateCheckerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private DuplicateChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->checker = new DuplicateChecker;
    }

    private function mockHandler(string $modelClass, array $duplicateKeys): ImportableEntity
    {
        $mock = $this->createMock(ImportableEntity::class);
        $mock->method('modelClass')->willReturn($modelClass);
        $mock->method('duplicateKeys')->willReturn($duplicateKeys);
        return $mock;
    }

    public function test_uses_with_trashed_on_soft_delete_models(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $emp = Employee::create([
            'company_id'            => $this->company->id,
            'employee_code'         => 'SOFT01',
            'first_name'            => 'Soft',
            'last_name'             => 'Deleted',
            'identification_type'   => 'CC',
            'identification_number' => 'SD999',
            'hire_date'             => '2020-01-01',
            'employment_status'     => 'terminated',
        ]);
        $emp->delete(); // soft delete

        $handler = $this->mockHandler(Employee::class, ['employee_code']);
        $row     = ['employee_code' => 'SOFT01'];

        $result = $this->checker->check($handler, $row, $this->company->id);

        $this->assertEquals('soft_deleted', $result,
            'DuplicateChecker debe detectar registros con SoftDelete (withTrashed)');
    }

    public function test_returns_none_when_duplicate_keys_empty(): void
    {
        $handler = $this->mockHandler(Employee::class, []);
        $row     = ['employee_code' => 'NOCHECK'];

        $result = $this->checker->check($handler, $row, $this->company->id);

        $this->assertEquals('none', $result,
            'Cuando duplicateKeys() es vacío, debe retornar none sin consultar la BD');
    }
}
