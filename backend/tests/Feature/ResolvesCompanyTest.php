<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\User;
use App\Models\WhatsappSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * H1 — Verifica que companyId() rechace usuarios sin empresa asociada.
 */
class ResolvesCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['conversations.view', 'conversations.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    /** Usuario autenticado con company_id válido puede listar conversaciones. */
    public function test_user_with_company_id_can_access_resource(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['conversations.view', 'conversations.manage']);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/conversations')->assertOk();
    }

    /** Usuario autenticado con company_id = null recibe 403. */
    public function test_user_with_null_company_id_is_forbidden(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->givePermissionTo(['conversations.view', 'conversations.manage']);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/conversations')->assertForbidden();
    }

    /** Petición sin autenticación recibe 401. */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/conversations')->assertUnauthorized();
    }

    /** Usuario de empresa A no puede ver conversaciones de empresa B. */
    public function test_user_cannot_see_conversations_from_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userA->givePermissionTo(['conversations.view', 'conversations.manage']);

        $settingB = WhatsappSetting::factory()->create(['company_id' => $companyB->id, 'is_active' => true]);
        $convB = Conversation::factory()->create([
            'company_id'          => $companyB->id,
            'whatsapp_setting_id' => $settingB->id,
        ]);

        Sanctum::actingAs($userA, ['*']);

        // index devuelve sólo empresa A — la lista debe estar vacía
        $response = $this->getJson('/api/conversations');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($convB->id, $ids, 'Empresa A no debe ver conversaciones de empresa B');
    }
}
