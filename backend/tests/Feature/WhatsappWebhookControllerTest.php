<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\WhatsappSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsappWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $verifyToken = 'test-verify-token-abc123';
    private string $appSecret   = 'test-app-secret-xyz789';

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();

        WhatsappSetting::create([
            'company_id'           => $company->id,
            'webhook_verify_token' => $this->verifyToken,
            'app_secret'           => $this->appSecret,
            'is_active'            => true,
        ]);
    }

    // ─── GET /webhooks/wa/{token} — verificación del webhook ───────────────────

    #[Test]
    public function verify_returns_challenge_when_token_and_mode_are_correct(): void
    {
        $response = $this->get("/api/webhooks/wa/{$this->verifyToken}?" . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_challenge'    => 'challenge_xyz',
            'hub_verify_token' => $this->verifyToken,
        ]));

        $response->assertStatus(200);
        $response->assertSee('challenge_xyz');
    }

    #[Test]
    public function verify_returns_403_when_token_is_wrong(): void
    {
        $response = $this->get('/api/webhooks/wa/wrong-token?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_challenge'    => 'challenge_xyz',
            'hub_verify_token' => 'wrong-token',
        ]));

        $response->assertStatus(403);
    }

    // ─── POST /webhooks/wa/{token} — recepción de mensajes ─────────────────────

    #[Test]
    public function receive_returns_200_when_hmac_signature_is_valid(): void
    {
        $payload   = '{"entry":[{"changes":[{"value":{"messages":[]}}]}]}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->appSecret);

        $response = $this->call(
            'POST',
            "/api/webhooks/wa/{$this->verifyToken}",
            [],
            [],
            [],
            ['HTTP_X_HUB_SIGNATURE_256' => $signature],
            $payload
        );

        $response->assertStatus(200);
    }

    #[Test]
    public function receive_returns_403_when_hmac_signature_is_invalid(): void
    {
        $payload   = '{"entry":[{"changes":[{"value":{"messages":[]}}]}]}';
        $signature = 'sha256=invalidsignaturevalue';

        $response = $this->call(
            'POST',
            "/api/webhooks/wa/{$this->verifyToken}",
            [],
            [],
            [],
            ['HTTP_X_HUB_SIGNATURE_256' => $signature],
            $payload
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function receive_returns_403_when_signature_header_is_missing(): void
    {
        $payload = '{"entry":[]}';

        $response = $this->call('POST', "/api/webhooks/wa/{$this->verifyToken}", [], [], [], [], $payload);

        $response->assertStatus(403);
    }
}
