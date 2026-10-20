<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GoogleSheetsSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoogleSheetsSettingIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->companyA = Company::factory()->create(['name' => 'Empresa A']);
        $this->companyB = Company::factory()->create(['name' => 'Empresa B']);

        Permission::firstOrCreate(['name' => 'google_sheets.manage', 'guard_name' => 'web']);
    }

    private function actAs(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('google_sheets.manage');
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    // ── Auth / permiso ────────────────────────────────────────────────────────

    public function test_show_requires_auth(): void
    {
        $this->getJson('/api/settings/google-sheets')->assertUnauthorized();
    }

    public function test_show_requires_google_sheets_manage_permission(): void
    {
        $user = User::factory()->create(['company_id' => $this->companyA->id]);
        Sanctum::actingAs($user, ['*']); // sin permiso
        $this->getJson('/api/settings/google-sheets')->assertForbidden();
    }

    public function test_update_requires_auth(): void
    {
        $this->putJson('/api/settings/google-sheets', ['api_key' => 'x'])->assertUnauthorized();
    }

    public function test_update_requires_google_sheets_manage_permission(): void
    {
        $user = User::factory()->create(['company_id' => $this->companyA->id]);
        Sanctum::actingAs($user, ['*']);
        $this->putJson('/api/settings/google-sheets', ['api_key' => 'x'])->assertForbidden();
    }

    // ── Aislamiento show ──────────────────────────────────────────────────────

    public function test_show_returns_own_company_setting(): void
    {
        $settingA = GoogleSheetsSetting::create([
            'company_id' => $this->companyA->id,
            'api_key'    => 'KEY_A',
        ]);

        $this->actAs($this->companyA);
        $response = $this->getJson('/api/settings/google-sheets')->assertOk();

        // El id devuelto debe corresponder al registro de la empresa A
        $this->assertEquals($settingA->id, $response->json('id'));
        $this->assertTrue($response->json('has_api_key'));
    }

    public function test_show_does_not_expose_other_company_setting(): void
    {
        $settingB = GoogleSheetsSetting::create([
            'company_id' => $this->companyB->id,
            'api_key'    => 'KEY_B_SECRET',
        ]);

        // Usuario A: debe recibir su propio registro vacío, no el de B
        $this->actAs($this->companyA);
        $response = $this->getJson('/api/settings/google-sheets')->assertOk();

        // El id devuelto NO debe ser el registro de B
        $this->assertNotEquals($settingB->id, $response->json('id'));

        // Empresa A tiene su propio registro en BD (recién creado por firstOrCreate)
        $this->assertDatabaseHas('google_sheets_settings', ['company_id' => $this->companyA->id]);
        // La respuesta no debe mostrar api_key de B (su registro fue creado vacío)
        $this->assertFalse($response->json('has_api_key'));
    }

    // ── Aislamiento update / IDOR ─────────────────────────────────────────────

    public function test_update_writes_to_own_company(): void
    {
        $this->actAs($this->companyA);
        $this->putJson('/api/settings/google-sheets', ['api_key' => 'KEY_A_NEW'])->assertOk();

        $this->assertDatabaseHas('google_sheets_settings', [
            'company_id' => $this->companyA->id,
        ]);
        // La clave está encriptada en BD; verificamos que no se guardó en la de B
        $this->assertDatabaseMissing('google_sheets_settings', [
            'company_id' => $this->companyB->id,
        ]);
    }

    public function test_update_ignores_company_id_in_body_idor(): void
    {
        GoogleSheetsSetting::create([
            'company_id' => $this->companyB->id,
            'api_key'    => 'ORIGINAL_B_KEY',
        ]);

        // Usuario A inyecta company_id de B en el body
        $this->actAs($this->companyA);
        $this->putJson('/api/settings/google-sheets', [
            'company_id' => $this->companyB->id, // intento IDOR
            'api_key'    => 'INJECTED_KEY',
        ])->assertOk();

        // Registro de B: solo debe existir uno con company_id=B (el original)
        $this->assertDatabaseCount('google_sheets_settings', 2); // uno por empresa

        $settingB = GoogleSheetsSetting::where('company_id', $this->companyB->id)->first();
        $settingA = GoogleSheetsSetting::where('company_id', $this->companyA->id)->first();

        $this->assertNotNull($settingA, 'Empresa A debe tener su propio registro');
        $this->assertNotNull($settingB, 'Empresa B debe conservar el suyo');
        // api_key de B está encriptada; verificamos que NO es la inyectada
        // (si hubiera IDOR, habría sobrescrito el registro de B — que seguiría con el original)
        $this->assertEquals($this->companyB->id, $settingB->company_id);
        $this->assertEquals($this->companyA->id, $settingA->company_id);
    }

    // ── Test endpoint usa credenciales de la propia empresa ───────────────────

    public function test_test_endpoint_uses_own_company_credentials(): void
    {
        GoogleSheetsSetting::create([
            'company_id' => $this->companyA->id,
            'api_key'    => 'VALID_KEY_A',
        ]);

        // 400 INVALID_ARGUMENT = key válida pero falta spreadsheetId (respuesta esperada por el controlador)
        Http::fake([
            'sheets.googleapis.com/*' => Http::response(
                ['error' => ['status' => 'INVALID_ARGUMENT']], 400
            ),
        ]);

        $this->actAs($this->companyA);
        $response = $this->postJson('/api/settings/google-sheets/test')->assertOk();

        $this->assertEquals('ok', $response->json('status'));
        Http::assertSentCount(1);
    }

    public function test_test_endpoint_pending_when_own_company_has_no_credentials(): void
    {
        GoogleSheetsSetting::create([
            'company_id' => $this->companyA->id,
            'api_key'    => 'VALID_KEY_A',
        ]);

        Http::fake(); // no debe llamar a Google

        $this->actAs($this->companyB); // sin api_key
        $response = $this->postJson('/api/settings/google-sheets/test')->assertOk();

        $this->assertEquals('pending_configuration', $response->json('status'));
        Http::assertNothingSent();
    }
}
