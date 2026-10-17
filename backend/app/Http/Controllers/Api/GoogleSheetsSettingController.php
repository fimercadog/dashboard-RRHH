<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoogleSheetsSettingResource;
use App\Models\GoogleSheetsSetting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleSheetsSettingController extends Controller
{
    use ResolvesCompany;

    public function show(Request $request): JsonResponse
    {
        $setting = GoogleSheetsSetting::firstOrCreate(['company_id' => $this->companyId($request)]);

        return response()->json(new GoogleSheetsSettingResource($setting));
    }

    public function update(Request $request, AuditService $audit): JsonResponse
    {
        $request->validate([
            'api_key' => 'nullable|string|max:500',
        ]);

        $cid     = $this->companyId($request);
        $setting = GoogleSheetsSetting::firstOrNew(['company_id' => $cid]);
        $old     = $setting->getOriginal();

        $data = [];
        if ($request->filled('api_key')) {
            $data['api_key'] = $request->input('api_key');
        }

        $setting->fill($data + ['company_id' => $cid])->save();
        $audit->record('updated', $setting, $request, $old);

        return response()->json(new GoogleSheetsSettingResource($setting->fresh()));
    }

    public function test(Request $request, AuditService $audit): JsonResponse
    {
        $setting = GoogleSheetsSetting::where('company_id', $this->companyId($request))->first();

        if (! $setting || ! filled($setting->api_key)) {
            return response()->json([
                'status' => 'pending_configuration', 'message' => 'Ingresa tu API Key antes de probar.',
            ]);
        }

        try {
            // Llamada real a Google Sheets API usando una hoja pública de prueba
            $response = Http::get('https://sheets.googleapis.com/v4/spreadsheets', [
                'key' => $setting->api_key,
            ]);

            // 400 con "INVALID_ARGUMENT" = key válida pero falta spreadsheetId (esperado)
            // 403 con "API_KEY_INVALID" = key inválida
            $body = $response->json();

            if ($response->status() === 403 || (isset($body['error']['status']) && $body['error']['status'] === 'PERMISSION_DENIED' && str_contains($body['error']['message'] ?? '', 'API key'))) {
                return response()->json([
                    'status' => 'error', 'message' => 'API Key inválida. Verifica que esté activa en Google Cloud Console.',
                ]);
            }

            // Cualquier otra respuesta (incluyendo 400 INVALID_ARGUMENT) indica key válida
            $setting->update(['is_active' => true, 'connected_at' => now()]);
            $audit->record('updated', $setting, $request, []);

            return response()->json([
                'status' => 'ok', 'message' => 'API Key válida. Google Sheets listo para usar.',
            ]);
        } catch (\Throwable) {
            return response()->json([
                'status' => 'error', 'message' => 'No se pudo conectar con Google. Verifica tu conexión.',
            ]);
        }
    }
}
