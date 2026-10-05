<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Company $otherCompany;
    private User $admin;
    private User $adminOther;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'communications.manage', 'communications.view', 'dashboard.view',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Administrador de empresa', 'guard_name' => 'web']);
        $adminRole->syncPermissions($perms);
        $empleadoRole = Role::firstOrCreate(['name' => 'Empleado', 'guard_name' => 'web']);
        $empleadoRole->syncPermissions(['dashboard.view']);

        $this->company      = Company::factory()->create(['name' => 'Test Comms SA']);
        $this->otherCompany = Company::factory()->create(['name' => 'Otra Empresa Comms']);

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole('Administrador de empresa');

        $this->adminOther = User::factory()->create(['company_id' => $this->otherCompany->id]);
        $this->adminOther->assignRole('Administrador de empresa');
    }

    public function test_campaigns_requires_auth(): void
    {
        $this->getJson('/api/campaigns')->assertStatus(401);
    }

    public function test_list_campaigns_requires_permission(): void
    {
        $limited = User::factory()->create(['company_id' => $this->company->id]);
        $limited->assignRole('Empleado');

        $this->actingAs($limited, 'sanctum')
            ->getJson('/api/campaigns')
            ->assertStatus(403);
    }

    public function test_create_campaign_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/campaigns', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'audience_source']);
    }

    public function test_create_campaign_validates_audience_source(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/campaigns', [
                'name'            => 'Test',
                'type'            => 'mass',
                'audience_source' => 'invalid_source',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['audience_source']);
    }

    public function test_create_campaign_stores_correctly(): void
    {
        $res = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/campaigns', [
                'name'            => 'Campaña de prueba',
                'type'            => 'mass',
                'audience_source' => 'erp_employees',
                'message_subject' => 'Hola',
                'message_body'    => 'Cuerpo del mensaje.',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('name', 'Campaña de prueba')
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('audience_source', 'erp_employees');

        $this->assertDatabaseHas('campaigns', [
            'name'       => 'Campaña de prueba',
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_company_isolation_campaign_belongs_to_company(): void
    {
        Campaign::factory()->create([
            'company_id' => $this->otherCompany->id,
            'created_by' => $this->adminOther->id,
            'name'       => 'Otra empresa',
            'audience_source' => 'erp_employees',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/campaigns');
        $names = collect($res->json('data'))->pluck('name')->all();

        $this->assertNotContains('Otra empresa', $names, 'Campaigns from other companies must not appear');
    }

    public function test_sources_endpoint_returns_all_sources(): void
    {
        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/campaigns/sources');

        $res->assertOk();
        $keys = collect($res->json())->pluck('key')->all();

        $this->assertContains('erp_employees', $keys);
        $this->assertContains('erp_clients', $keys);
        $this->assertContains('erp_leads', $keys);
        $this->assertContains('google_sheets', $keys);
        $this->assertContains('csv', $keys);
    }

    public function test_google_sheets_source_is_not_ready(): void
    {
        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/campaigns/sources');

        $sheets = collect($res->json())->firstWhere('key', 'google_sheets');

        $this->assertNotNull($sheets);
        $this->assertFalse($sheets['ready'], 'Google Sheets must be not-ready without credentials');
        $this->assertNotEmpty($sheets['message'], 'Google Sheets must provide a not-ready message');
    }

    public function test_preview_audience_erp_employees(): void
    {
        Employee::factory()->count(4)->create(['company_id' => $this->company->id, 'employment_status' => 'active']);

        $campaign = Campaign::factory()->create([
            'company_id'      => $this->company->id,
            'created_by'      => $this->admin->id,
            'audience_source' => 'erp_employees',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience");

        $res->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonStructure(['ready', 'count', 'sample']);

        $this->assertGreaterThanOrEqual(4, $res->json('count'));
    }

    public function test_preview_audience_erp_clients(): void
    {
        Client::factory()->count(3)->create(['company_id' => $this->company->id]);

        $campaign = Campaign::factory()->create([
            'company_id'      => $this->company->id,
            'created_by'      => $this->admin->id,
            'audience_source' => 'erp_clients',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience");

        $res->assertOk()->assertJsonPath('ready', true);
        $this->assertGreaterThanOrEqual(3, $res->json('count'));
    }

    public function test_preview_audience_google_sheets_returns_not_ready(): void
    {
        $campaign = Campaign::factory()->create([
            'company_id'      => $this->company->id,
            'created_by'      => $this->admin->id,
            'audience_source' => 'google_sheets',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience");

        $res->assertOk()->assertJsonPath('ready', false);
        $this->assertNotEmpty($res->json('message'));
    }

    public function test_company_isolation_cannot_access_other_company_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'company_id'      => $this->otherCompany->id,
            'created_by'      => $this->adminOther->id,
            'audience_source' => 'erp_employees',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience")
            ->assertStatus(403);
    }

    public function test_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'company_id'      => $this->company->id,
            'created_by'      => $this->admin->id,
            'audience_source' => 'erp_employees',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/campaigns/{$campaign->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }
}
