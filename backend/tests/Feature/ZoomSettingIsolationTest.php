<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\ZoomSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ZoomSettingIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->companyA = Company::factory()->create(['name' => 'Empresa A']);
        $this->companyB = Company::factory()->create(['name' => 'Empresa B']);

        Permission::firstOrCreate(['name' => 'zoom.manage', 'guard_name' => 'web']);
    }

    private function actAs(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('zoom.manage');
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    // ── Auth / permiso ────────────────────────────────────────────────────────

    public function test_show_requires_auth(): void
    {
        $this->getJson('/api/settings/zoom')->assertUnauthorized();
    }

    public function test_show_requires_zoom_manage_permission(): void
    {
        $user = User::factory()->create(['company_id' => $this->companyA->id]);
        Sanctum::actingAs($user, ['*']); // sin permiso zoom.manage
        $this->getJson('/api/settings/zoom')->assertForbidden();
    }

    public function test_update_requires_auth(): void
    {
        $this->putJson('/api/settings/zoom', ['account_id' => 'x'])->assertUnauthorized();
    }

    public function test_update_requires_zoom_manage_permission(): void
    {
        $user = User::factory()->create(['company_id' => $this->companyA->id]);
        Sanctum::actingAs($user, ['*']);
        $this->putJson('/api/settings/zoom', ['account_id' => 'x'])->assertForbidden();
    }

    // ── Aislamiento show ──────────────────────────────────────────────────────

    public function test_show_returns_own_company_setting(): void
    {
        $settingA = ZoomSetting::create([
            'company_id' => $this->companyA->id,
            'account_id' => 'ACCT_A',
            'client_id'  => 'CID_A',
        ]);

        $this->actAs($this->companyA);
        $response = $this->getJson('/api/settings/zoom')->assertOk();

        // El id devuelto debe corresponder al registro de la empresa A
        $this->assertEquals($settingA->id, $response->json('id'));
        $this->assertEquals('ACCT_A', $response->json('account_id'));
    }

    public function test_show_does_not_expose_other_company_setting(): void
    {
        $settingB = ZoomSetting::create([
            'company_id' => $this->companyB->id,
            'account_id' => 'ACCT_B_SECRET',
            'client_id'  => 'CID_B',
        ]);

        // Usuario de empresa A — show debe crearle un registro vacío, no devolver el de B
        $this->actAs($this->companyA);
        $response = $this->getJson('/api/settings/zoom')->assertOk();

        // El id devuelto NO debe ser el registro de B
        $this->assertNotEquals($settingB->id, $response->json('id'));

        // Empresa A tiene ahora su propio registro en BD
        $this->assertDatabaseHas('zoom_settings', ['company_id' => $this->companyA->id]);
        // account_id de B no debe aparecer en la respuesta
        $this->assertNotEquals('ACCT_B_SECRET', $response->json('account_id'));
    }

    // ── Aislamiento update / IDOR ─────────────────────────────────────────────

    public function test_update_writes_to_own_company(): void
    {
        $this->actAs($this->companyA);
        $this->putJson('/api/settings/zoom', ['account_id' => 'NEW_ACCT_A'])->assertOk();

        $this->assertDatabaseHas('zoom_settings', [
            'company_id' => $this->companyA->id,
            'account_id' => 'NEW_ACCT_A',
        ]);
    }

    public function test_update_ignores_company_id_in_body_idor(): void
    {
        // Empresa B ya tiene su propia configuración
        ZoomSetting::create([
            'company_id' => $this->companyB->id,
            'account_id' => 'ORIGINAL_B',
        ]);

        // Usuario A intenta inyectar company_id de B en el body
        $this->actAs($this->companyA);
        $this->putJson('/api/settings/zoom', [
            'company_id' => $this->companyB->id, // intento IDOR
            'account_id' => 'INJECTED',
        ])->assertOk();

        // Empresa B no debe haber sido modificada
        $this->assertDatabaseHas('zoom_settings', [
            'company_id' => $this->companyB->id,
            'account_id' => 'ORIGINAL_B',
        ]);

        // Empresa A recibió la actualización
        $this->assertDatabaseHas('zoom_settings', [
            'company_id' => $this->companyA->id,
            'account_id' => 'INJECTED',
        ]);
    }

    // ── Test endpoint usa credenciales de la propia empresa ───────────────────

    public function test_test_endpoint_uses_own_company_credentials(): void
    {
        // Solo empresa A tiene configuración completa
        ZoomSetting::create([
            'company_id'    => $this->companyA->id,
            'account_id'    => 'ACCT_A',
            'client_id'     => 'CID_A',
            'client_secret' => 'SEC_A',
        ]);
        // Empresa B no tiene configuración
        ZoomSetting::create(['company_id' => $this->companyB->id]);

        Http::fake(['zoom.us/*' => Http::response(['access_token' => 'tok'], 200)]);

        $this->actAs($this->companyA);
        $response = $this->postJson('/api/settings/zoom/test')->assertOk();

        $this->assertEquals('ok', $response->json('status'));
        Http::assertSentCount(1);
    }

    public function test_test_endpoint_pending_when_own_company_has_no_credentials(): void
    {
        // Usuario B pide test sin credenciales propias — no debe usar las de A
        ZoomSetting::create([
            'company_id'    => $this->companyA->id,
            'account_id'    => 'ACCT_A',
            'client_id'     => 'CID_A',
            'client_secret' => 'SEC_A',
        ]);

        Http::fake(); // no debe llamar a Zoom

        $this->actAs($this->companyB);
        $response = $this->postJson('/api/settings/zoom/test')->assertOk();

        $this->assertEquals('pending_configuration', $response->json('status'));
        Http::assertNothingSent();
    }
}
