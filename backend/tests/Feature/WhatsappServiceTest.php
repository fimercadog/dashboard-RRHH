<?php

namespace Tests\Feature;

use App\Models\WhatsappSetting;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhatsappService();
    }

    public function test_send_text_returns_wamid(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(
                ['messages' => [['id' => 'wamid.abc123']]],
                200
            ),
        ]);

        $setting = WhatsappSetting::factory()->create([
            'phone_number_id' => '12345678',
            'access_token'    => 'fake-token',
        ]);

        $wamid = $this->service->sendText($setting, '573001234567', 'Hola', $setting->company_id);

        $this->assertSame('wamid.abc123', $wamid);
    }

    public function test_send_text_throws_on_4xx(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => 'invalid token'], 401),
        ]);

        $setting = WhatsappSetting::factory()->create([
            'phone_number_id' => '12345678',
            'access_token'    => 'bad-token',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->sendText($setting, '573001234567', 'Hola', $setting->company_id);
    }

    public function test_send_text_exception_does_not_contain_token(): void
    {
        $token = 'super-secret-token-xyz';

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $setting = WhatsappSetting::factory()->create([
            'phone_number_id' => '12345678',
            'access_token'    => $token,
        ]);

        try {
            $this->service->sendText($setting, '573001234567', 'Hola', $setting->company_id);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString($token, $e->getMessage());
        }
    }

    public function test_send_text_throws_on_company_mismatch(): void
    {
        $setting = WhatsappSetting::factory()->create([
            'phone_number_id' => '12345678',
            'access_token'    => 'fake-token',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('company mismatch');

        $this->service->sendText($setting, '573001234567', 'Hola', $setting->company_id + 999);
    }
}
