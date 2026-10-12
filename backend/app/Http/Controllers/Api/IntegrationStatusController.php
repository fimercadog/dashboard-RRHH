<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Models\WhatsappSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $wa = WhatsappSetting::where('company_id', $companyId)->first();
        $ai = AiSetting::where('company_id', $companyId)->first();

        return response()->json([
            'whatsapp' => [
                'is_connected' => $wa?->isConnected() ?? false,
                'is_active'    => $wa?->is_active ?? false,
            ],
            'ai' => [
                'is_connected' => $ai?->isConnected() ?? false,
                'is_active'    => $ai?->is_active ?? false,
                'provider'     => $ai?->provider ?? null, // nombre del proveedor, SIN api_key
            ],
        ]);
    }
}
