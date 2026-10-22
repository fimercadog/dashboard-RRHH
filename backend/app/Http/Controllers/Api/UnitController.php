<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends BaseCrudController
{
    protected string $model    = Unit::class;
    protected string $resource = UnitResource::class;
    protected array  $searchable = ['name', 'abbreviation'];
    protected array  $filterable = ['status'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Unit::where('company_id', $cid);
        return response()->json([
            'total'  => $q->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
