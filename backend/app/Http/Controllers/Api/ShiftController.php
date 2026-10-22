<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends BaseCrudController
{
    protected string $model = Shift::class;
    protected string $resource = ShiftResource::class;
    protected array $searchable = ['name'];
    protected array $filterable = ['status' => 'status'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = Shift::where('company_id', $cid);
        return response()->json([
            'total'  => $q->clone()->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
