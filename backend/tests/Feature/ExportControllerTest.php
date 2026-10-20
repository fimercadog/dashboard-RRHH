<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ExportController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company      = Company::factory()->create(['name' => 'Empresa A']);
        $this->otherCompany = Company::factory()->create(['name' => 'Empresa B']);

        foreach (['employees.manage', 'dashboard.view', 'finance.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    private function actAs(array $permissions = []): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo($permissions);
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    // ── Auth / permiso ────────────────────────────────────────────────────────

    public function test_csv_without_auth_returns_401(): void
    {
        $this->get('/api/exports/employees.csv')->assertUnauthorized();
    }

    public function test_csv_without_permission_returns_403(): void
    {
        $this->actAs(['dashboard.view']);
        $this->get('/api/exports/employees.csv')->assertForbidden();
    }

    public function test_pdf_without_permission_returns_403(): void
    {
        $this->actAs(['dashboard.view']);
        $this->get('/api/exports/employees.pdf')->assertForbidden();
    }

    public function test_unknown_resource_returns_404(): void
    {
        $this->actAs(['employees.manage']);
        $this->get('/api/exports/nonexistent.csv')->assertNotFound();
    }

    public function test_resource_in_map_but_absent_from_permissions_returns_403_not_500(): void
    {
        Permission::firstOrCreate(['name' => 'employees.manage', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo('employees.manage');

        // Simula: developer agrega recurso a $map pero olvida $permissionByResource
        $ctrl = new ExportController();
        $prop = new \ReflectionProperty(ExportController::class, 'permissionByResource');
        $prop->setAccessible(true);
        $perms = $prop->getValue($ctrl);
        unset($perms['employees']);
        $prop->setValue($ctrl, $perms);
        $this->app->instance(ExportController::class, $ctrl);

        Sanctum::actingAs($user, ['*']);
        $this->get('/api/exports/employees.csv')->assertForbidden();
    }

    public function test_unknown_format_returns_404(): void
    {
        $this->actAs(['employees.manage']);
        $this->get('/api/exports/employees.xlsx')->assertNotFound();
    }

    // ── Nombre de archivo descriptivo ────────────────────────────────────────

    public function test_csv_filename_is_descriptive_with_date(): void
    {
        $this->actAs(['employees.manage']);

        $date     = now()->format('Y-m-d');
        $response = $this->get('/api/exports/employees.csv');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', "attachment; filename=empleados_{$date}.csv");
    }

    public function test_pdf_filename_is_descriptive_with_date(): void
    {
        $this->actAs(['employees.manage']);

        $date     = now()->format('Y-m-d');
        $response = $this->get('/api/exports/employees.pdf');

        $response->assertOk();
        $this->assertStringContainsString("empleados_{$date}.pdf", $response->headers->get('Content-Disposition'));
    }

    // ── Formato y contenido CSV ───────────────────────────────────────────────

    public function test_csv_has_utf8_bom(): void
    {
        $this->actAs(['employees.manage']);

        $response = $this->get('/api/exports/employees.csv');
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'CSV debe empezar con BOM UTF-8');
    }

    public function test_csv_content_type_is_correct(): void
    {
        $this->actAs(['employees.manage']);

        $response = $this->get('/api/exports/employees.csv');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_csv_has_header_row(): void
    {
        $this->actAs(['employees.manage']);

        $response = $this->get('/api/exports/employees.csv');
        $response->assertOk();

        $lines = explode("\n", ltrim($response->streamedContent(), "\xEF\xBB\xBF"));
        // Primera línea = cabeceras
        $this->assertStringContainsString('employee_code', $lines[0]);
    }

    // ── Aislamiento multi-tenant ──────────────────────────────────────────────

    public function test_csv_only_exports_own_company_records(): void
    {
        // Registro de la empresa del usuario logueado
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Propia',
        ]);
        // Registro de otra empresa — no debe aparecer
        Employee::factory()->create([
            'company_id' => $this->otherCompany->id,
            'first_name' => 'Ajena',
        ]);

        $this->actAs(['employees.manage']);

        $content = $this->get('/api/exports/employees.csv')->streamedContent();

        $this->assertStringContainsString('Propia', $content);
        $this->assertStringNotContainsString('Ajena', $content);
    }

    // ── CSV Injection sanitization ────────────────────────────────────────────

    public function test_csv_injection_is_sanitized(): void
    {
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => '=CMD|calc.exe',
        ]);

        $this->actAs(['employees.manage']);

        $content = $this->get('/api/exports/employees.csv')->streamedContent();

        // fputcsv separa campos con coma; un campo no protegido arrancaría con ",=CMD"
        $this->assertStringNotContainsString(',=CMD', $content, 'El valor CSV injection debe estar sanitizado (no debe arrancar campo con =)');
        $this->assertStringContainsString("'=CMD", $content, 'El valor debe tener el prefijo de protección');
    }

    // ── Finanzas (recurso con permiso distinto) ──────────────────────────────

    public function test_payments_export_requires_finance_manage(): void
    {
        $this->actAs(['dashboard.view']);
        $this->get('/api/exports/payments.csv')->assertForbidden();
    }

    public function test_payments_export_allowed_with_finance_manage(): void
    {
        $this->actAs(['finance.manage']);
        $this->get('/api/exports/payments.csv')->assertOk();
    }

    public function test_payments_csv_filename_uses_spanish_name(): void
    {
        $this->actAs(['finance.manage']);
        $date     = now()->format('Y-m-d');
        $response = $this->get('/api/exports/payments.csv');
        $response->assertOk();
        $this->assertStringContainsString("pagos_{$date}.csv", $response->headers->get('Content-Disposition'));
    }
}
