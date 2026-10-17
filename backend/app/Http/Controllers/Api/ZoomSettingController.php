<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\ZoomSettingResource;
use App\Models\ZoomSetting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZoomSettingController extends Controller
{
    use ResolvesCompany;

    public function show(Request $request): JsonResponse
    {
        $setting = ZoomSetting::firstOrCreate(['company_id' => $this->companyId($request)]);

        return response()->json(new ZoomSettingResource($setting));
    }

    public function update(Request $request, AuditService $audit): JsonResponse
    {
        $request->validate([
            'account_id'    => 'nullable|string|max:255',
            'client_id'     => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:1000',
        ]);

        $cid     = $this->companyId($request);
        $setting = ZoomSetting::firstOrNew(['company_id' => $cid]);
        $old     = $setting->getOriginal();

        $data = $request->only(['account_id', 'client_id']);
        if ($request->filled('client_secret')) {
            $data['client_secret'] = $request->input('client_secret');
        }

        $setting->fill($data + ['company_id' => $cid])->save();
        $audit->record('updated', $setting, $request, $old);

        return response()->json(new ZoomSettingResource($setting->fresh()));
    }

    public function test(Request $request, AuditService $audit): JsonResponse
    {
        $setting = ZoomSetting::where('company_id', $this->companyId($request))->first();

        if (! $setting || ! filled($setting->account_id) || ! filled($setting->client_id) || ! filled($setting->client_secret)) {
            return response()->json([
                'status' => 'pending_configuration', 'message' => 'Completa Account ID, Client ID y Client Secret antes de probar.',
            ]);
        }

        try {
            $response = Http::withBasicAuth($setting->client_id, $setting->client_secret)
                ->asForm()
                ->post("https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$setting->account_id}");

            if ($response->successful() && $response->json('access_token')) {
                $setting->update(['is_active' => true, 'connected_at' => now()]);
                $audit->record('updated', $setting, $request, []);

                return response()->json([
                    'status' => 'ok', 'message' => 'Conexión exitosa con Zoom.',
                ]);
            }

            return response()->json([
                'status' => 'error', 'message' => 'Credenciales inválidas. Verifica Account ID, Client ID y Client Secret.',
            ]);
        } catch (\Throwable) {
            return response()->json([
                'status' => 'error', 'message' => 'No se pudo conectar con Zoom. Verifica tu conexión.',
            ]);
        }
    }
}
