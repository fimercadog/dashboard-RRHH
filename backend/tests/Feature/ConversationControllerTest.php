<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Models\WhatsappSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['conversations.view', 'conversations.manage', 'conversations.close'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->givePermissionTo(['conversations.view', 'conversations.manage', 'conversations.close']);

        $setting = WhatsappSetting::factory()->create([
            'company_id'   => $company->id,
            'is_active'    => true,
            'access_token' => 'fake-test-token',
        ]);

        $this->conversation = Conversation::factory()->create([
            'company_id'           => $company->id,
            'whatsapp_setting_id'  => $setting->id,
            'status'               => 'open',
            'last_customer_msg_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    private function makeMessage(Conversation $conv, array $attrs = []): ConversationMessage
    {
        return ConversationMessage::factory()->create(array_merge(
            ['conversation_id' => $conv->id, 'status' => 'delivered'],
            $attrs,
        ));
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_list_is_scoped_by_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherSetting = WhatsappSetting::factory()->create(['company_id' => $otherCompany->id]);
        $otherConv    = Conversation::factory()->create([
            'company_id'          => $otherCompany->id,
            'whatsapp_setting_id' => $otherSetting->id,
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['id' => $otherConv->id]);
    }

    public function test_list_filters_by_status(): void
    {
        // reuse existing setting — company_id is unique in whatsapp_settings
        $settingId = $this->conversation->whatsapp_setting_id;

        Conversation::factory()->create([
            'company_id'          => $this->admin->company_id,
            'whatsapp_setting_id' => $settingId,
            'status'              => 'closed',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/conversations?status=closed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'closed');
    }

    public function test_list_requires_view_permission(): void
    {
        $user = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/conversations')
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_conversation_detail(): void
    {
        $this->actingAsAdmin()
            ->getJson("/api/conversations/{$this->conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->conversation->id)
            ->assertJsonPath('data.status', 'open');
    }

    public function test_show_returns_404_for_other_company_conversation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherSetting = WhatsappSetting::factory()->create(['company_id' => $otherCompany->id]);
        $otherConv    = Conversation::factory()->create([
            'company_id'          => $otherCompany->id,
            'whatsapp_setting_id' => $otherSetting->id,
        ]);

        $this->actingAsAdmin()
            ->getJson("/api/conversations/{$otherConv->id}")
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // messages (history)
    // -------------------------------------------------------------------------

    public function test_messages_returns_history_in_asc_order(): void
    {
        $first  = $this->makeMessage($this->conversation, ['created_at' => now()->subMinutes(5)]);
        $second = $this->makeMessage($this->conversation, ['created_at' => now()]);

        $response = $this->actingAsAdmin()
            ->getJson("/api/conversations/{$this->conversation->id}/messages")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], $ids);
    }

    public function test_messages_returns_404_for_other_company_conversation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherSetting = WhatsappSetting::factory()->create(['company_id' => $otherCompany->id]);
        $otherConv    = Conversation::factory()->create([
            'company_id'          => $otherCompany->id,
            'whatsapp_setting_id' => $otherSetting->id,
        ]);

        $this->actingAsAdmin()
            ->getJson("/api/conversations/{$otherConv->id}/messages")
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // assign
    // -------------------------------------------------------------------------

    public function test_assign_updates_assigned_to(): void
    {
        $agent = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/assign", ['user_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $agent->id);

        $this->assertDatabaseHas('conversations', [
            'id'          => $this->conversation->id,
            'assigned_to' => $agent->id,
        ]);
    }

    public function test_assign_rejects_user_from_different_company(): void
    {
        $otherUser = User::factory()->create(); // company_id diferente

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/assign", ['user_id' => $otherUser->id])
            ->assertUnprocessable();

        $this->assertDatabaseHas('conversations', [
            'id'          => $this->conversation->id,
            'assigned_to' => null,
        ]);
    }

    public function test_assign_requires_manage_permission(): void
    {
        $viewOnly = User::factory()->create(['company_id' => $this->admin->company_id]);
        $viewOnly->givePermissionTo('conversations.view');
        $agent = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAs($viewOnly, 'sanctum')
            ->postJson("/api/conversations/{$this->conversation->id}/assign", ['user_id' => $agent->id])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // transfer / close / reopen
    // -------------------------------------------------------------------------

    public function test_transfer_changes_status_to_transferred(): void
    {
        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/transfer")
            ->assertOk()
            ->assertJsonPath('data.status', 'transferred');

        $this->assertDatabaseHas('conversations', ['id' => $this->conversation->id, 'status' => 'transferred']);
    }

    public function test_close_changes_status_to_closed(): void
    {
        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('conversations', ['id' => $this->conversation->id, 'status' => 'closed']);
    }

    public function test_close_requires_close_permission(): void
    {
        $noClose = User::factory()->create(['company_id' => $this->admin->company_id]);
        $noClose->givePermissionTo(['conversations.view', 'conversations.manage']);

        $this->actingAs($noClose, 'sanctum')
            ->postJson("/api/conversations/{$this->conversation->id}/close")
            ->assertForbidden();
    }

    public function test_reopen_changes_status_to_open(): void
    {
        $this->conversation->update(['status' => 'closed']);

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/reopen")
            ->assertOk()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('conversations', ['id' => $this->conversation->id, 'status' => 'open']);
    }

    // -------------------------------------------------------------------------
    // sendMessage
    // -------------------------------------------------------------------------

    public function test_send_message_creates_outbound_record(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.manual1']]], 200),
        ]);

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/messages", ['content' => 'Hola, ¿en qué te ayudo?'])
            ->assertCreated()
            ->assertJsonPath('data.direction', 'outbound')
            ->assertJsonPath('data.sender_type', 'agent')
            ->assertJsonPath('data.meta_message_id', 'wamid.manual1');

        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $this->conversation->id,
            'direction'       => 'outbound',
            'content'         => 'Hola, ¿en qué te ayudo?',
            'sender_type'     => 'agent',
            'meta_message_id' => 'wamid.manual1',
        ]);
    }

    public function test_send_message_returns_422_outside_24h_window(): void
    {
        $this->conversation->update([
            'last_customer_msg_at' => Carbon::parse('2000-01-01'),
        ]);

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/messages", ['content' => 'Tarde'])
            ->assertUnprocessable();

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_send_message_returns_503_on_provider_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([], 500),
        ]);

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/messages", ['content' => 'Falla'])
            ->assertStatus(503);

        $this->assertSame(0, ConversationMessage::where('direction', 'outbound')->count());
    }

    public function test_send_message_returns_422_when_setting_inactive(): void
    {
        $this->conversation->whatsappSetting->update(['is_active' => false]);

        $this->actingAsAdmin()
            ->postJson("/api/conversations/{$this->conversation->id}/messages", ['content' => 'Test'])
            ->assertUnprocessable();
    }

    public function test_send_message_requires_manage_permission(): void
    {
        $viewOnly = User::factory()->create(['company_id' => $this->admin->company_id]);
        $viewOnly->givePermissionTo('conversations.view');

        $this->actingAs($viewOnly, 'sanctum')
            ->postJson("/api/conversations/{$this->conversation->id}/messages", ['content' => 'Test'])
            ->assertForbidden();
    }
}
