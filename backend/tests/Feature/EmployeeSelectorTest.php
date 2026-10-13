<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeSelectorTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->company = Company::where('name', 'Andes People Solutions')->firstOrFail();
        $this->admin   = User::where('email', 'admin@andespeople.co')->firstOrFail();
        Sanctum::actingAs($this->admin, ['*']);
    }

    public function test_returns_at_most_30_by_default(): void
    {
        $response = $this->getJson('/api/employees/selector');
        $response->assertOk();
        $this->assertLessThanOrEqual(30, count($response->json()));
        $this->assertIsArray($response->json());
    }

    public function test_limit_param_respected(): void
    {
        $response = $this->getJson('/api/employees/selector?limit=5');
        $response->assertOk();
        $this->assertLessThanOrEqual(5, count($response->json()));
    }

    public function test_limit_capped_at_50(): void
    {
        // Sequence evita colisión de employee_code único por empresa con los del seeder
        Employee::factory()->count(60)
            ->sequence(fn (\Illuminate\Database\Eloquent\Factories\Sequence $seq) => [
                'employee_code' => 'TST-'.str_pad((string) ($seq->index + 1), 4, '0', STR_PAD_LEFT),
            ])
            ->create([
                'company_id'        => $this->company->id,
                'employment_status' => 'active',
            ]);
        $response = $this->getJson('/api/employees/selector?limit=100');
        $response->assertOk();
        $this->assertLessThanOrEqual(50, count($response->json()));
    }

    public function test_search_filters_by_name(): void
    {
        Employee::factory()->create([
            'company_id'        => $this->company->id,
            'first_name'        => 'Zoraida',
            'last_name'         => 'Testunique',
            'employment_status' => 'active',
        ]);
        $response = $this->getJson('/api/employees/selector?search=Testunique');
        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('Testunique', $data[0]['label']);
    }

    public function test_search_less_than_2_chars_ignores_filter(): void
    {
        $response = $this->getJson('/api/employees/selector?search=a');
        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    public function test_id_param_returns_employee_regardless_of_status(): void
    {
        $inactive = Employee::factory()->create([
            'company_id'        => $this->company->id,
            'employment_status' => 'inactive',
        ]);
        $response = $this->getJson("/api/employees/selector?id={$inactive->id}");
        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($inactive->id, $data[0]['id']);
    }

    public function test_id_of_other_company_returns_empty(): void
    {
        $otherCompany  = Company::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'company_id'        => $otherCompany->id,
            'employment_status' => 'active',
        ]);
        $response = $this->getJson("/api/employees/selector?id={$otherEmployee->id}");
        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->app['auth']->forgetGuards();
        $response = $this->getJson('/api/employees/selector');
        $this->assertContains($response->status(), [401, 302]);
    }

    public function test_response_is_always_array(): void
    {
        $response = $this->getJson('/api/employees/selector');
        $response->assertOk();
        $this->assertIsArray($response->json());
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_company_isolation(): void
    {
        $otherCompany  = Company::factory()->create();
        Employee::factory()->create([
            'company_id'        => $otherCompany->id,
            'employment_status' => 'active',
            'first_name'        => 'OtroEmpresaX99',
        ]);
        $response = $this->getJson('/api/employees/selector');
        $response->assertOk();
        $labels = array_column($response->json(), 'label');
        foreach ($labels as $label) {
            $this->assertStringNotContainsString('OtroEmpresaX99', $label);
        }
    }
}
