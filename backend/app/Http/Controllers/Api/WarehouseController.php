<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends BaseCrudController
{
    protected string $model    = Warehouse::class;
    protected string $resource = WarehouseResource::class;
    protected array  $searchable = ['name', 'location'];
    protected array  $filterable = ['status'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Warehouse::where('company_id', $cid);
        return response()->json([
            'total'  => $q->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
