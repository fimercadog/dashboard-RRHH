<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\WhatsappSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WhatsappSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos si no existen (las migraciones los insertan en producción,
        // pero RefreshDatabase limpia la BD en tests).
        Permission::firstOrCreate(['name' => 'whatsapp.manage', 'guard_name' => 'sanctum']);

        $company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->givePermissionTo('whatsapp.manage');
    }

    public function test_show_returns_empty_when_no_settings(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/settings/whatsapp');

        $response->assertOk()
            ->assertJsonPath('has_access_token', false)
            ->assertJsonPath('has_webhook_token', false)
            ->assertJsonMissing(['access_token'])
            ->assertJsonMissing(['webhook_verify_token']);
    }

    public function test_update_saves_credentials(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/whatsapp', [
                'phone_number_id'      => '123456',
                'waba_id'              => '789012',
                'access_token'         => 'EAAtest123',
                'webhook_verify_token' => 'my-verify-token-secure-enough',
            ]);

        $response->assertOk()
            ->assertJsonPath('phone_number_id', '123456')
            ->assertJsonPath('has_access_token', true)
            ->assertJsonPath('has_webhook_token', true)
            ->assertJsonMissing(['access_token'])
            ->assertJsonMissing(['webhook_verify_token']);

        // Verifica que access_token está cifrado en BD.
        $raw = \DB::table('whatsapp_settings')
            ->where('company_id', $this->admin->company_id)
            ->value('access_token');

        $this->assertNotEquals('EAAtest123', $raw, 'access_token debe estar cifrado en BD');
    }

    public function test_update_preserves_token_when_empty_string_sent(): void
    {
        WhatsappSetting::factory()->create([
            'company_id'           => $this->admin->company_id,
            'access_token'         => 'EAAoriginal',
            'webhook_verify_token' => 'orig-token',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/whatsapp', [
                'phone_number_id' => '999',
                'access_token'    => '',  // vacío → conservar
            ])
            ->assertOk()
            ->assertJsonPath('has_access_token', true);
    }

    public function test_generate_token_returns_plain_token_once(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/settings/whatsapp/generate-token');

        $response->assertOk()
            ->assertJsonStructure(['webhook_verify_token']);

        $token = $response->json('webhook_verify_token');
        $this->assertNotEmpty($token);

        // El token debe estar guardado en BD (plain, no cifrado).
        $stored = WhatsappSetting::where('company_id', $this->admin->company_id)
            ->value('webhook_verify_token');
        $this->assertEquals($token, $stored);
    }

    public function test_webhook_verify_challenge(): void
    {
        WhatsappSetting::factory()->create([
            'company_id'           => $this->admin->company_id,
            'webhook_verify_token' => 'test-slug',
        ]);

        $response = $this->getJson('/api/webhooks/wa/test-slug?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_challenge'    => 'challenge123',
            'hub_verify_token' => 'test-slug',
        ]));

        $response->assertOk()
            ->assertSee('challenge123');
    }

    public function test_webhook_verify_rejects_unknown_token(): void
    {
        $this->getJson('/api/webhooks/wa/unknown-token?' . http_build_query([
            'hub_mode'      => 'subscribe',
            'hub_challenge' => 'challenge123',
        ]))->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/settings/whatsapp')->assertUnauthorized();
    }

    public function test_user_without_permission_is_rejected(): void
    {
        $noPermUser = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/settings/whatsapp')
            ->assertForbidden();
    }

    public function test_cannot_access_other_companys_settings(): void
    {
        $otherCompany = Company::factory()->create();
        WhatsappSetting::factory()->create([
            'company_id'      => $otherCompany->id,
            'phone_number_id' => 'other-phone',
        ]);

        // La empresa del admin no tiene settings → devuelve vacío (no los de otra empresa).
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/settings/whatsapp');

        $response->assertOk()
            ->assertJsonPath('data.phone_number_id', null);
    }
}
