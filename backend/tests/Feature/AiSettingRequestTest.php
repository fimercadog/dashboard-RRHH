<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Validates SSRF-prevention rules on the base_url field
 * of the AiSetting store/update endpoint.
 */
class AiSettingRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'ai.manage', 'guard_name' => 'sanctum']);

        $company     = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->givePermissionTo('ai.manage');
    }

    public function test_base_url_with_http_scheme_is_rejected(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/ai', [
                'provider' => 'openai_compat',
                'base_url' => 'http://api.example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['base_url']);
    }

    public function test_base_url_with_private_ip_is_rejected(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/ai', [
                'provider' => 'openai_compat',
                'base_url' => 'http://192.168.1.1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['base_url']);
    }

    public function test_base_url_with_https_public_domain_is_accepted(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings/ai', [
                'provider' => 'openai_compat',
                'base_url' => 'https://api.openai.com',
            ])
            ->assertStatus(200);
    }
}
