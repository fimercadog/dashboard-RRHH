<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiSettingRequest;
use App\Http\Resources\AiSettingResource;
use App\Models\AiSetting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiSettingController extends Controller
{
    use ResolvesCompany;

    /** GET /settings/ai */
    public function show(Request $request): JsonResponse
    {
        $setting = $this->resolveOrCreate($request);

        return response()->json(['data' => new AiSettingResource($setting)]);
    }

    /** PUT /settings/ai */
    public function update(
        StoreAiSettingRequest $request,
        AuditService $audit
    ): JsonResponse {
        $cid     = $this->companyId($request);
        $setting = AiSetting::firstOrNew(['company_id' => $cid]);
        $old     = $setting->getOriginal();

        $data = $request->validated();

        // Si se omite api_key en el update, conservar la existente.
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $setting->fill($data + ['company_id' => $cid]);
        $setting->save();

        $audit->record('updated', $setting, $request, $old);

        return response()->json(['data' => new AiSettingResource($setting->fresh())]);
    }

    /**
     * POST /settings/ai/test
     * Envía un completion de prueba con el proveedor configurado.
     * Sin credenciales reales retorna estado pendiente.
     */
    public function test(Request $request): JsonResponse
    {
        $setting = $this->resolveOrCreate($request);

        if (! filled($setting->api_key)) {
            return response()->json([
                'data' => [
                    'status'  => 'pending_configuration',
                    'message' => 'Configura provider, model y api_key antes de probar.',
                ],
            ]);
        }

        // ponytail: mock branch — en local con mocks activos, respuesta simulada.
        if (app()->environment('local') && config('app.dev_mocks_enabled')) {
            $now = now();
            $setting->update(['last_tested_at' => $now, 'is_active' => true]);

            return response()->json([
                'data' => [
                    'status'   => 'mock_ok',
                    'provider' => $setting->provider,
                    'model'    => $setting->model ?? 'mock-model',
                    'latency'  => 120,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'status'  => 'not_tested',
                'message' => 'Test real requiere conectividad con el proveedor de IA.',
            ],
        ]);
    }

    /**
     * GET /settings/ai/models
     * Lista modelos disponibles para el proveedor configurado.
     * Sin credenciales devuelve lista estática.
     */
    public function models(Request $request): JsonResponse
    {
        $setting = $this->resolveOrCreate($request);

        $defaults = [
            'openai'        => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
            'anthropic'     => ['claude-sonnet-4-5', 'claude-haiku-4-5', 'claude-opus-4-5'],
            'openai_compat' => [],
        ];

        $models = $defaults[$setting->provider] ?? [];

        return response()->json(['data' => $models]);
    }

    private function resolveOrCreate(Request $request): AiSetting
    {
        return AiSetting::firstOrCreate(
            ['company_id' => $this->companyId($request)],
            ['provider' => 'openai']
        );
    }
}
