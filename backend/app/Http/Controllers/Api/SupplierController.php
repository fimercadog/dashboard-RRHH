<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends BaseCrudController
{
    protected string $model    = Supplier::class;
    protected string $resource = SupplierResource::class;
    protected array $searchable  = ['name', 'nit', 'email'];
    protected array $filterable  = ['status' => 'status'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Supplier::where('company_id', $cid);
        return response()->json([
            'total'  => $q->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
