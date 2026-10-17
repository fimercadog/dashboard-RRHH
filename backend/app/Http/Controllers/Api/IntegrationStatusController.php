<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Models\GoogleSheetsSetting;
use App\Models\WhatsappSetting;
use App\Models\ZoomSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $wa     = WhatsappSetting::where('company_id', $companyId)->first();
        $ai     = AiSetting::where('company_id', $companyId)->first();
        $zoom   = ZoomSetting::where('company_id', $companyId)->first();
        $sheets = GoogleSheetsSetting::where('company_id', $companyId)->first();

        return response()->json([
            'whatsapp' => [
                'is_connected' => $wa?->isConnected() ?? false,
                'is_active'    => $wa?->is_active ?? false,
            ],
            'ai' => [
                'is_connected' => $ai?->isConnected() ?? false,
                'is_active'    => $ai?->is_active ?? false,
                'provider'     => $ai?->provider ?? null,
            ],
            'zoom' => [
                'is_connected' => $zoom?->isConnected() ?? false,
                'is_active'    => $zoom?->is_active ?? false,
            ],
            'google_sheets' => [
                'is_connected' => $sheets?->isConnected() ?? false,
                'is_active'    => $sheets?->is_active ?? false,
            ],
        ]);
    }
}
