<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * CA-17: GET /employees/selector responde en < 200 ms con 100k empleados activos.
 */
class EmployeeSelectorPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const TARGET_MS = 200;
    private const TOTAL_EMPLOYEES = 100_000;
    private const CHUNK_SIZE = 2_000;

    public function test_selector_responds_under_200ms_with_100k_employees(): void
    {
        // Arrange — empresa y usuario sin seeder completo (demasiado lento a esta escala)
        $company = Company::factory()->create();

        Permission::firstOrCreate(['name' => 'employees.manage', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user, ['*']);

        // Bulk insert 100k empleados activos en chunks para no agotar memoria
        $now   = now()->toDateTimeString();
        $chunk = [];

        for ($i = 1; $i <= self::TOTAL_EMPLOYEES; $i++) {
            $chunk[] = [
                'company_id'            => $company->id,
                'employee_code'         => 'EMP'.$i,
                'first_name'            => 'Nombre'.$i,
                'last_name'             => 'Apellido'.($i % 1000),
                'identification_type'   => 'CC',
                'identification_number' => 'ID'.$i,
                'birth_date'            => '1990-01-01',
                'gender'                => 'male',
                'hire_date'             => '2020-01-01',
                'employment_status'     => 'active',
                'salary'                => 1000000,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];

            if (count($chunk) === self::CHUNK_SIZE) {
                DB::table('employees')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk) {
            DB::table('employees')->insert($chunk);
        }

        $this->assertDatabaseCount('employees', self::TOTAL_EMPLOYEES);

        // Act — medir tiempo real de respuesta del endpoint
        $start    = hrtime(true);
        $response = $this->getJson('/api/employees/selector');
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        // Assert — respuesta correcta y dentro del umbral
        $response->assertOk();
        $this->assertIsArray($response->json());
        $this->assertLessThanOrEqual(30, count($response->json()),
            'El selector devolvió más de 30 registros con el límite por defecto.');

        $this->assertLessThan(
            self::TARGET_MS,
            $elapsedMs,
            sprintf(
                'CA-17 FALLIDO: GET /employees/selector tardó %.2f ms con %d empleados (límite: %d ms).',
                $elapsedMs,
                self::TOTAL_EMPLOYEES,
                self::TARGET_MS,
            )
        );
    }
}
