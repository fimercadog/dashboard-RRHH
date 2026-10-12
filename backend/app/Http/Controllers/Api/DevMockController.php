<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Mocks de desarrollo para integraciones externas.
 * SOLO disponible cuando DEV_MOCKS_ENABLED=true y APP_ENV != production.
 *
 * Rutas registradas en routes/api.php SOLO cuando config('app.dev_mocks_enabled') es true.
 */
class DevMockController extends Controller
{
    // ─── WhatsApp Cloud API mock ─────────────────────────────────────────────

    /**
     * Simula el envío de un mensaje de texto saliente.
     * POST /dev/mocks/wa/messages
     */
    public function waSend(Request $request): JsonResponse
    {
        return response()->json([
            'messaging_product' => 'whatsapp',
            'contacts'          => [['input' => $request->input('to', '573001234567'), 'wa_id' => '573001234567']],
            'messages'          => [['id' => 'mock_wamid_' . uniqid()]],
        ]);
    }

    /**
     * Simula un mensaje entrante de WhatsApp (webhook inbound).
     * POST /dev/mocks/wa/inbound
     * Envía el payload al webhook real de la empresa (requiere token).
     */
    public function waInbound(Request $request): JsonResponse
    {
        $token = $request->input('webhook_token', '');
        $from  = $request->input('from', '573001234567');
        $text  = $request->input('text', 'Hola, esto es un mensaje de prueba');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry'  => [[
                'id'      => 'mock_waba',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata'          => ['display_phone_number' => '573001234567', 'phone_number_id' => 'mock_pnid'],
                        'contacts'          => [['profile' => ['name' => 'Usuario Mock'], 'wa_id' => $from]],
                        'messages'          => [[
                            'from'      => $from,
                            'id'        => 'mock_wamid_' . uniqid(),
                            'timestamp' => (string) time(),
                            'text'      => ['body' => $text],
                            'type'      => 'text',
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        // Reenvía al webhook interno si hay token.
        if ($token) {
            \Illuminate\Support\Facades\Http::post(url("/api/webhooks/wa/{$token}"), $payload);
        }

        return response()->json(['status' => 'mock_inbound_sent', 'payload' => $payload]);
    }

    /**
     * Simula el estado de entrega de un mensaje.
     * GET /dev/mocks/wa/status
     */
    public function waStatus(): JsonResponse
    {
        return response()->json([
            'status'    => 'delivered',
            'wamid'     => 'mock_wamid_' . uniqid(),
            'timestamp' => time(),
        ]);
    }

    // ─── OpenAI-compatible AI mock ───────────────────────────────────────────

    /**
     * Simula una respuesta de completions (OpenAI-compatible).
     * POST /dev/mocks/ai/chat/completions
     */
    public function aiCompletions(Request $request): JsonResponse
    {
        $messages = $request->input('messages', []);
        $last     = end($messages);
        $prompt   = is_array($last) ? ($last['content'] ?? '') : '';

        return response()->json([
            'id'      => 'mock_chatcmpl_' . uniqid(),
            'object'  => 'chat.completion',
            'created' => time(),
            'model'   => $request->input('model', 'mock-model'),
            'choices' => [[
                'index'         => 0,
                'message'       => ['role' => 'assistant', 'content' => "Mock response to: {$prompt}"],
                'finish_reason' => 'stop',
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 15, 'total_tokens' => 25],
        ]);
    }

    /**
     * Lista modelos simulados (OpenAI-compatible).
     * GET /dev/mocks/ai/models
     */
    public function aiModels(): JsonResponse
    {
        return response()->json([
            'object' => 'list',
            'data'   => [
                ['id' => 'mock-gpt-4o', 'object' => 'model'],
                ['id' => 'mock-claude-sonnet', 'object' => 'model'],
            ],
        ]);
    }
}
