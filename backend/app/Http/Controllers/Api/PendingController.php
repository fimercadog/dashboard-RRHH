<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Services\PendingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingController extends Controller
{
    use ResolvesCompany;

    public function __construct(private readonly PendingService $pending) {}

    public function __invoke(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $result    = $this->pending->all($companyId, $request->user());

        return response()->json($result);
    }
}
