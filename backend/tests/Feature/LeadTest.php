<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_submit_a_lead_with_consent(): void
    {
        $this->postJson('/api/leads', [
            'name' => 'Ana Prueba',
            'email' => 'ana@empresa.co',
            'source' => 'demo',
            'consent' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('leads', ['email' => 'ana@empresa.co', 'source' => 'demo', 'status' => 'new']);
    }

    public function test_lead_without_consent_is_rejected(): void
    {
        $this->postJson('/api/leads', [
            'name' => 'Ana Prueba',
            'email' => 'ana@empresa.co',
            'source' => 'demo',
        ])->assertStatus(422)->assertJsonValidationErrors('consent');
    }

    public function test_lead_list_requires_permission(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/leads')->assertForbidden();
    }

    public function test_user_with_permission_lists_and_updates_status(): void
    {
        Permission::firstOrCreate(['name' => 'leads.view', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('leads.view');
        Sanctum::actingAs($user, ['*']);

        $lead = Lead::create(['name' => 'X', 'email' => 'x@y.co', 'source' => 'contact', 'status' => 'new']);

        $this->getJson('/api/leads')->assertOk()->assertJsonPath('data.0.id', $lead->id);
        $this->putJson("/api/leads/{$lead->id}", ['status' => 'contacted'])->assertOk();
        $this->assertSame('contacted', $lead->refresh()->status);
    }
}
