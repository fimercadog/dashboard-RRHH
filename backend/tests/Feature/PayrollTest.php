<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyTaxProfile;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\PayrollItem;
use App\Models\PayrollParameter;
use App\Models\PayrollParameterValue;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private Company     $company;
    private User        $adminUser;
    private PayrollPeriod $period;

    // Valores legales 2026
    private const SMMLV         = 1750905;
    private const AUX_TRANSPORT = 249095;
    private const UVT           = 52374;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        foreach (['payroll.view', 'payroll.manage', 'payroll.close', 'payroll.reverse', 'payroll.params.manage'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->adminUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->adminUser->givePermissionTo(['payroll.manage', 'payroll.close', 'payroll.view']);

        $this->seedPayrollParameters();

        $this->period = PayrollPeriod::create([
            'company_id'   => $this->company->id,
            'name'         => 'Octubre 2026',
            'period_type'  => 'monthly',
            'start_date'   => '2026-10-01',
            'end_date'     => '2026-10-31',
            'payment_date' => '2026-10-31',
            'status'       => 'draft',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function seedPayrollParameters(): void
    {
        $defs = [
            ['smmlv',                            'SMMLV',              self::SMMLV,  'COP',     '2026-01-01', '2026-10-20'],
            ['auxilio_transporte',               'Auxilio transporte', self::AUX_TRANSPORT, 'COP', '2026-01-01', '2026-10-20'],
            ['uvt',                              'UVT',                self::UVT,    'COP',     '2026-01-01', '2026-10-20'],
            ['salud_empleado_pct',               'Salud emp',           4.00,        'percent', '2026-01-01', '2026-10-20'],
            ['salud_empresa_pct',                'Salud empresa',       8.50,        'percent', '2026-01-01', '2026-10-20'],
            ['pension_empleado_pct',             'Pensión emp',         4.00,        'percent', '2026-01-01', '2026-10-20'],
            ['pension_empresa_pct',              'Pensión empresa',    12.00,        'percent', '2026-01-01', '2026-10-20'],
            ['sena_pct',                         'SENA',                2.00,        'percent', '2026-01-01', '2026-10-20'],
            ['icbf_pct',                         'ICBF',                3.00,        'percent', '2026-01-01', '2026-10-20'],
            ['caja_compensacion_pct',            'CCF',                 4.00,        'percent', '2026-01-01', '2026-10-20'],
            ['arl_clase_1_pct',                  'ARL I',               0.522,       'percent', '2026-01-01', '2026-10-20'],
            ['arl_clase_2_pct',                  'ARL II',              1.044,       'percent', '2026-01-01', '2026-10-20'],
            ['arl_clase_3_pct',                  'ARL III',             2.436,       'percent', '2026-01-01', '2026-10-20'],
            ['arl_clase_4_pct',                  'ARL IV',              4.350,       'percent', '2026-01-01', '2026-10-20'],
            ['arl_clase_5_pct',                  'ARL V',               6.960,       'percent', '2026-01-01', '2026-10-20'],
            ['tope_auxilio_transporte_smmlv',    'Tope aux',            2.00,        'SMMLV',   '2026-01-01', '2026-10-20'],
            ['ibc_maximo_smmlv',                 'IBC máx',            25.00,        'SMMLV',   '2026-01-01', '2026-10-20'],
            ['fondo_solidaridad_umbral_smmlv',   'FSP umbral',          4.00,        'SMMLV',   '2026-01-01', '2026-10-20'],
            ['retefuente_renta_exenta_pct',      'Renta exenta',       25.00,        'percent', '2026-01-01', '2026-10-20'],
            ['retefuente_renta_exenta_tope_uvt', 'Tope renta exenta', 240.00,        'UVT',     '2026-01-01', '2026-10-20'],
        ];

        foreach ($defs as [$code, $name, $value, $unit, $from, $verifiedAt]) {
            $param = PayrollParameter::create([
                'code'       => $code,
                'name'       => $name,
                'scope'      => 'global',
                'company_id' => null,
            ]);
            PayrollParameterValue::create([
                'parameter_id'   => $param->id,
                'effective_from' => $from,
                'value'          => $value,
                'unit'           => $unit,
                'source_norm'    => 'Test',
                'verified_at'    => $verifiedAt,
            ]);
        }
    }

    private function makeEmployee(?Company $company = null): Employee
    {
        $co   = $company ?? $this->company;
        $dept = Department::factory()->create(['company_id' => $co->id]);
        $pos  = Position::factory()->create(['company_id' => $co->id, 'department_id' => $dept->id]);
        return Employee::factory()->create([
            'company_id'    => $co->id,
            'department_id' => $dept->id,
            'position_id'   => $pos->id,
        ]);
    }

    private function makeContract(Employee $emp, float $salary, string $modality = 'ordinary', int $riskClass = 1): EmployeeContract
    {
        static $seq = 0;
        $seq++;
        return EmployeeContract::create([
            'company_id'          => $emp->company_id,
            'employee_id'         => $emp->id,
            'contract_number'     => 'CT-TEST-' . $seq,
            'contract_type'       => 'indefinido',
            'salary_modality'     => $modality,
            'start_date'          => '2026-01-01',
            'salary'              => $salary,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => $riskClass,
            'status'              => 'active',
        ]);
    }

    private function makeValidatedTaxProfile(?Company $company = null, bool $exempt = false): CompanyTaxProfile
    {
        $co = $company ?? $this->company;
        // forceFill porque review_status, verified_by, verified_at están fuera de $fillable
        // (solo asignables vía el endpoint /validate en producción)
        $profile = (new CompanyTaxProfile())->forceFill([
            'company_id'           => $co->id,
            'legal_nature'         => 'SAS',
            'tax_contributor_type' => 'Gran contribuyente',
            'parafiscal_exempt'    => $exempt,
            'review_status'        => 'validated',
            'verified_by'          => 'Contador certificado',
            'verified_at'          => '2026-01-01',
            'valid_from'           => '2026-01-01',
        ]);
        $profile->save();
        return $profile;
    }

    private function service(): PayrollService
    {
        return app(PayrollService::class);
    }

    private function itemFor(int $runId, int $employeeId): ?PayrollItem
    {
        return PayrollItem::where('payroll_run_id', $runId)
            ->where('employee_id', $employeeId)
            ->first();
    }

    // ── Test 1: Sin contrato activo → excluido del run ────────────────────────

    public function test_employee_without_active_contract_excluded_from_run(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp = $this->makeEmployee();
        // Sin contrato — no hacemos makeContract()

        $run = $this->service()->process($this->period);

        $this->assertEquals(0, $run->total_employees);
        $this->assertNull($this->itemFor($run->id, $emp->id));
    }

    // ── Test 2: Salario < 2×SMMLV → auxilio incluido ─────────────────────────

    public function test_transport_included_when_salary_below_double_smmlv(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp = $this->makeEmployee();
        $this->makeContract($emp, self::SMMLV + 100000); // < 2×SMMLV

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        $this->assertNotNull($item);
        $this->assertEquals((float) self::AUX_TRANSPORT, (float) $item->transport_allowance);
    }

    // ── Test 3: Salario > 2×SMMLV → sin auxilio ──────────────────────────────

    public function test_transport_excluded_when_salary_above_double_smmlv(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp = $this->makeEmployee();
        $this->makeContract($emp, self::SMMLV * 2 + 1); // justo sobre el tope

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        $this->assertNotNull($item);
        $this->assertEquals(0.0, (float) $item->transport_allowance);
    }

    // ── Test 4: Deducciones empleado: salud 4%, pensión 4% ───────────────────

    public function test_employee_deductions_health_4pct_pension_4pct(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp    = $this->makeEmployee();
        $salary = 3_000_000.0;
        $this->makeContract($emp, $salary);

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        // IBC ordinary = salary (clamp: salary > SMMLV, no need to adjust)
        $ibc              = max($salary, (float) self::SMMLV);
        $expectedHealth   = round($ibc * 0.04, 2);
        $expectedPension  = round($ibc * 0.04, 2);

        $this->assertEquals($expectedHealth,  (float) $item->health_employee);
        $this->assertEquals($expectedPension, (float) $item->pension_employee);
    }

    // ── Test 5: Aportes empresa: salud 8.5%, pensión 12% ─────────────────────

    public function test_employer_contributions_health_8_5pct_pension_12pct(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp    = $this->makeEmployee();
        $salary = 3_000_000.0;
        $this->makeContract($emp, $salary);

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        $ibc              = max($salary, (float) self::SMMLV);
        $expectedHealth   = round($ibc * 0.085, 2);
        $expectedPension  = round($ibc * 0.12,  2);

        $this->assertEquals($expectedHealth,  (float) $item->health_employer);
        $this->assertEquals($expectedPension, (float) $item->pension_employer);
    }

    // ── Test 6a: Parafiscales presentes sin perfil validado ──────────────────

    public function test_parafiscales_applied_without_validated_tax_profile(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp    = $this->makeEmployee();
        $salary = 3_000_000.0;
        $this->makeContract($emp, $salary);
        // Sin tax profile → aplica SENA + ICBF por defecto

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        $ibc          = max($salary, (float) self::SMMLV);
        $expectedSena = round($ibc * 0.02, 2);
        $expectedIcbf = round($ibc * 0.03, 2);

        $this->assertEquals($expectedSena, (float) $item->sena_employer);
        $this->assertEquals($expectedIcbf, (float) $item->icbf_employer);
    }

    // ── Test 6b: Parafiscales cero cuando validado y exento ──────────────────

    public function test_parafiscales_zero_when_validated_and_exempt(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp    = $this->makeEmployee();
        $salary = 3_000_000.0; // < 10×SMMLV → aplica exoneración
        $this->makeContract($emp, $salary);
        $this->makeValidatedTaxProfile($this->company, exempt: true);

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        $this->assertEquals(0.0, (float) $item->sena_employer);
        $this->assertEquals(0.0, (float) $item->icbf_employer);
        $this->assertTrue((bool) $item->parafiscal_exempt);
    }

    // ── Test 7: CA-N — cierre bloqueado / permitido ───────────────────────────

    public function test_close_blocked_when_smmlv_not_verified(): void
    {
        Sanctum::actingAs($this->adminUser);

        $param = PayrollParameter::where('code', 'smmlv')->first();
        PayrollParameterValue::where('parameter_id', $param->id)
            ->update(['verified_at' => null]);

        $missing = $this->period->canClose();
        $this->assertContains('smmlv_not_verified', $missing);
    }

    public function test_close_allowed_when_all_params_verified(): void
    {
        Sanctum::actingAs($this->adminUser);
        $this->makeValidatedTaxProfile();

        $missing = $this->period->canClose();
        $this->assertEmpty($missing);
    }

    // ── Test 8: Aislamiento multiempresa ──────────────────────────────────────

    public function test_run_excludes_employees_from_other_company(): void
    {
        Sanctum::actingAs($this->adminUser);

        $empA = $this->makeEmployee($this->company);
        $this->makeContract($empA, 3_000_000.0);

        $companyB = Company::factory()->create();
        $empB     = $this->makeEmployee($companyB);
        EmployeeContract::create([
            'company_id'          => $companyB->id,
            'employee_id'         => $empB->id,
            'contract_number'     => 'CT-B-FOREIGN',
            'contract_type'       => 'indefinido',
            'salary_modality'     => 'ordinary',
            'start_date'          => '2026-01-01',
            'salary'              => 3_000_000,
            'salary_type'         => 'monthly',
            'work_hours_per_week' => 48,
            'risk_class'          => 1,
            'status'              => 'active',
        ]);

        $run = $this->service()->process($this->period);

        $this->assertNotNull($this->itemFor($run->id, $empA->id), 'Empleado A debe estar en el run');
        $this->assertNull($this->itemFor($run->id, $empB->id),    'Empleado B (otra empresa) no debe estar en el run');
    }

    // ── Test 9: Inmutabilidad — período cerrado no se puede procesar ──────────

    public function test_closed_period_cannot_be_processed(): void
    {
        Sanctum::actingAs($this->adminUser);
        $this->period->update(['status' => 'closed']);

        $this->expectException(HttpException::class);
        $this->service()->process($this->period->fresh());
    }

    // ── Test 10: Permisos de API ──────────────────────────────────────────────

    public function test_payroll_manage_permission_required_to_run(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        Sanctum::actingAs($user); // sin payroll.manage

        $this->postJson("/api/payroll/periods/{$this->period->id}/run")
            ->assertForbidden();
    }

    public function test_payroll_close_permission_required_to_close(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        Sanctum::actingAs($user); // sin payroll.close

        $this->postJson("/api/payroll/periods/{$this->period->id}/close")
            ->assertForbidden();
    }

    // ── Test: approve() — run completado pasa a approved ─────────────────────
    public function test_approve_completed_run_transitions_to_approved(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp = $this->makeEmployee();
        $this->makeContract($emp, 3_000_000.0);

        $run = $this->service()->process($this->period);
        $this->assertEquals('completed', $run->status);

        $this->postJson("/api/payroll/runs/{$run->id}/approve")
            ->assertOk();

        $this->assertEquals('approved', $run->fresh()->status);
        $this->assertTrue(
            $run->items()->where('status', 'approved')->exists(),
            'Los ítems deben estar aprobados'
        );
    }

    // ── Test: approve() — doble aprobación retorna 422 ───────────────────────
    public function test_approve_already_approved_run_returns_422(): void
    {
        Sanctum::actingAs($this->adminUser);
        $emp = $this->makeEmployee();
        $this->makeContract($emp, 3_000_000.0);

        $run = $this->service()->process($this->period);
        $this->postJson("/api/payroll/runs/{$run->id}/approve")->assertOk();

        $this->postJson("/api/payroll/runs/{$run->id}/approve")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'El run ya está aprobado.']);
    }

    // ── Test: approve() — run no completado retorna 422 ─────────────────────
    public function test_approve_non_completed_run_returns_422(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Crear un run manualmente en estado 'running' (sin completar)
        $run = new \App\Models\PayrollRun();
        $run->forceFill([
            'company_id'          => $this->company->id,
            'payroll_period_id'   => $this->period->id,
            'run_number'          => 1,
            'executed_by'         => $this->adminUser->id,
            'executed_at'         => now(),
            'parameters_snapshot' => [],
            'status'              => 'running',
        ])->save();

        $this->postJson("/api/payroll/runs/{$run->id}/approve")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Solo se pueden aprobar runs completados.']);
    }

    // ── Test: close() HTTP — 422 con missing_params cuando falta SMMLV ───────
    public function test_close_endpoint_returns_422_with_missing_params_when_smmlv_unverified(): void
    {
        Sanctum::actingAs($this->adminUser);

        $param = PayrollParameter::where('code', 'smmlv')->first();
        PayrollParameterValue::where('parameter_id', $param->id)
            ->update(['verified_at' => null]);

        $this->postJson("/api/payroll/periods/{$this->period->id}/close")
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'missing_params'])
            ->assertJsonFragment(['missing_params' => ['smmlv_not_verified', 'company_tax_profile_not_validated']]);
    }

    // ── Test: close() HTTP — 200 cuando todos los parámetros verificados ─────
    public function test_close_endpoint_returns_200_when_all_params_verified(): void
    {
        Sanctum::actingAs($this->adminUser);
        $this->makeValidatedTaxProfile();

        $this->postJson("/api/payroll/periods/{$this->period->id}/close")
            ->assertOk()
            ->assertJsonPath('status', 'closed');
    }

    // ── Test: contrato integral — IBC = 70%, sin auxilio, warning VP-006 ─────
    public function test_integral_salary_uses_70pct_ibc_no_transport_vp006_warning(): void
    {
        Sanctum::actingAs($this->adminUser);
        $salary = 15_000_000.0; // 15M — bien por encima de 2×SMMLV
        $emp    = $this->makeEmployee();
        $this->makeContract($emp, $salary, 'integral');

        $run  = $this->service()->process($this->period);
        $item = $this->itemFor($run->id, $emp->id);

        $expectedIbc = round($salary * 0.70, 2);

        $this->assertNotNull($item);
        $this->assertEquals($expectedIbc,  (float) $item->ibc,                 'IBC debe ser 70% del salario integral');
        $this->assertEquals(0.0,           (float) $item->transport_allowance,  'Sin auxilio de transporte en salario integral');
        $this->assertEquals(0.0,           (float) $item->withholding_tax,      'Sin retención — VP-006');

        // El run debe registrar el warning VP-006
        $this->assertStringContainsString('VP-006', $run->fresh()->error_log ?? '');
    }
}
