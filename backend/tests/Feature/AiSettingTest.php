<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AiSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'ai.manage', 'guard_name' => 'sanctum']);

        $company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->givePermissionTo('ai.manage');
    }

    public function test_show_returns_empty_when_no_settings(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/settings/ai')
            ->assertOk()
            ->assertJsonPath('data.has_api_key', false)
            ->assertJsonMissing(['api_key']);
    }

    public function test_update_saves_provider_and_key(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/ai', [
                'provider' => 'openai',
                'model'    => 'gpt-4o-mini',
                'api_key'  => 'sk-test123',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.provider', 'openai')
            ->assertJsonPath('data.has_api_key', true)
            ->assertJsonMissing(['api_key']);

        // api_key debe estar cifrado en BD.
        $raw = \DB::table('ai_settings')
            ->where('company_id', $this->admin->company_id)
            ->value('api_key');

        $this->assertNotEquals('sk-test123', $raw, 'api_key debe estar cifrado en BD');
    }

    public function test_update_rejects_invalid_provider(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/ai', ['provider' => 'invalid-provider'])
            ->assertUnprocessable();
    }

    public function test_models_endpoint_returns_list(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/settings/ai/models');

        $response->assertOk()
            ->assertJsonStructure(['data']);

        $this->assertIsArray($response->json('data'));
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/settings/ai')->assertUnauthorized();
    }

    public function test_user_without_permission_is_rejected(): void
    {
        $noPermUser = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAs($noPermUser, 'sanctum')
            ->getJson('/api/settings/ai')
            ->assertForbidden();
    }

    public function test_cannot_access_other_companys_ai_settings(): void
    {
        $otherCompany = Company::factory()->create();
        AiSetting::factory()->create([
            'company_id' => $otherCompany->id,
            'model'      => 'gpt-4',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/settings/ai');

        $response->assertOk()
            ->assertJsonPath('data.model', null);
    }
}
