<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseCrudController
{
    protected string $model    = Product::class;
    protected string $resource = ProductResource::class;
    protected array  $with     = ['category:id,name', 'brand:id,name', 'unit:id,name,abbreviation'];
    protected array  $searchable = ['sku', 'name'];
    protected array  $filterable = ['status', 'type', 'category_id', 'brand_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Product::where('company_id', $cid);
        return response()->json([
            'total'  => $q->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
