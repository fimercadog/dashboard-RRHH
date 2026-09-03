<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_response_does_not_reveal_whether_email_exists(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'real@andespeople.co', 'status' => 'active']);

        $known = $this->postJson('/api/auth/forgot-password', ['email' => 'real@andespeople.co']);
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'nope@nowhere.co']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $payload = ['email' => 'real@andespeople.co', 'password' => 'wrong'];

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', $payload);
        }

        $this->postJson('/api/auth/login', $payload)->assertStatus(429);
    }

    public function test_security_headers_are_present(): void
    {
        $this->getJson('/api/auth/me')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_unknown_api_route_returns_generic_404(): void
    {
        $this->getJson('/api/ruta-que-no-existe')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Recurso no encontrado.']);
    }
}
