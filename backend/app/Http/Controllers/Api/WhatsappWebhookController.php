<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsappWebhookJob;
use App\Models\WhatsappSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Ruta PÚBLICA — fuera de auth:sanctum.
 * GET  /webhooks/wa/{token}  → verificación del webhook (Meta challenge)
 * POST /webhooks/wa/{token}  → recepción de mensajes entrantes (Fase 1)
 */
class WhatsappWebhookController extends Controller
{
    /**
     * Verificación del webhook (GET).
     * Meta envía hub.mode=subscribe, hub.challenge y hub.verify_token.
     * Si el token coincide con el almacenado, devuelve el challenge y marca verified.
     */
    public function verify(Request $request, string $token): Response
    {
        $mode      = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');
        $verifyToken = $request->query('hub_verify_token');

        if ($mode !== 'subscribe' || empty($challenge)) {
            return response('Bad Request', 400);
        }

        $setting = WhatsappSetting::where('webhook_verify_token', $token)->first();

        if (! $setting) {
            return response('Forbidden', 403);
        }

        // hub_verify_token must match — reject if absent or wrong.
        if ($verifyToken !== $token) {
            return response('Forbidden', 403);
        }

        $setting->update([
            'webhook_verified_at' => now(),
            'is_active'           => true,
        ]);

        return response((string) $challenge, 200);
    }

    /**
     * Recepción de mensajes (POST) — stub para Fase 1.
     * Por ahora solo acusa recibo (200 OK) para que Meta no reintente.
     */
    public function receive(Request $request, string $token): Response
    {
        // Leer cuerpo RAW antes de cualquier parseo (requerido por HMAC).
        $rawBody = $request->getContent();

        // Valida que el token corresponda a alguna empresa activa.
        $setting = WhatsappSetting::where('webhook_verify_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            return response('Forbidden', 403);
        }

        // Validar firma HMAC-SHA256 enviada por Meta en X-Hub-Signature-256.
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return response('Forbidden', 403);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $setting->app_secret);

        if (! hash_equals($expected, $signature)) {
            return response('Forbidden', 403);
        }

        // Decode payload and dispatch async processing.
        $decoded = json_decode($rawBody, true) ?? [];
        ProcessWhatsappWebhookJob::dispatch($decoded, $setting->id);

        return response('OK', 200);
    }
}
