<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWhatsappSettingRequest;
use App\Http\Resources\WhatsappSettingResource;
use App\Models\WhatsappSetting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsappSettingController extends Controller
{
    use ResolvesCompany;

    /** GET /settings/whatsapp — leer config de la empresa. */
    public function show(Request $request): JsonResponse
    {
        $setting = $this->resolveOrCreate($request);

        return response()->json(new WhatsappSettingResource($setting));
    }

    /** PUT /settings/whatsapp — guardar config. */
    public function update(
        StoreWhatsappSettingRequest $request,
        AuditService $audit
    ): JsonResponse {
        $cid     = $this->companyId($request);
        $setting = WhatsappSetting::firstOrNew(['company_id' => $cid]);
        $old     = $setting->getOriginal();

        $data = $request->validated();

        // Si se omite la API key en el update, conservar la existente.
        if (empty($data['access_token'])) {
            unset($data['access_token']);
        }
        if (empty($data['webhook_verify_token'])) {
            unset($data['webhook_verify_token']);
        }

        $setting->fill($data + ['company_id' => $cid]);
        $setting->save();

        $audit->record('updated', $setting, $request, $old);

        return response()->json(new WhatsappSettingResource($setting->fresh()));
    }

    /**
     * POST /settings/whatsapp/generate-token
     * Genera un webhook_verify_token seguro para la empresa.
     */
    public function generateToken(Request $request, AuditService $audit): JsonResponse
    {
        $cid     = $this->companyId($request);
        $setting = WhatsappSetting::firstOrNew(['company_id' => $cid]);
        $old     = $setting->getOriginal();

        $token = Str::random(32);
        $setting->fill([
            'company_id'           => $cid,
            'webhook_verify_token' => $token,
        ])->save();

        $audit->record('updated', $setting, $request, $old);

        // El token se devuelve UNA sola vez aquí para que el usuario lo copie en Meta.
        return response()->json(['webhook_verify_token' => $token]);
    }

    /**
     * POST /settings/whatsapp/test
     * Verifica que el access_token pueda obtener el perfil del número.
     * Sin credenciales reales retorna estado pendiente.
     */
    public function test(Request $request): JsonResponse
    {
        $setting = $this->resolveOrCreate($request);

        if (! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            return response()->json([
                'status'  => 'pending_configuration',
                'message' => 'Configura phone_number_id y access_token antes de probar.',
            ]);
        }

        // ponytail: mock branch — cuando APP_ENV=local y DEV_MOCKS_ENABLED, no llamamos a Meta.
        if (app()->environment('local') && config('app.dev_mocks_enabled')) {
            return response()->json([
                'status'       => 'mock_ok',
                'display_name' => 'Mock Business (dev)',
                'phone_number' => '+57 300 000 0000',
            ]);
        }

        return response()->json([
            'status'  => 'not_tested',
            'message' => 'Test real requiere conectividad con Meta Graph API.',
        ]);
    }

    private function resolveOrCreate(Request $request): WhatsappSetting
    {
        return WhatsappSetting::firstOrCreate(
            ['company_id' => $this->companyId($request)],
            []
        );
    }
}
