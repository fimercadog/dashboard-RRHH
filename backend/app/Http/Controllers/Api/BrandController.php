<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends BaseCrudController
{
    protected string $model    = Brand::class;
    protected string $resource = BrandResource::class;
    protected array  $searchable = ['name'];
    protected array  $filterable = ['status'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Brand::where('company_id', $cid);
        return response()->json([
            'total'  => $q->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
