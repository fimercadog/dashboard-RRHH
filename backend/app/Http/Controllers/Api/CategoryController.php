<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseCrudController
{
    protected string $model    = Category::class;
    protected string $resource = CategoryResource::class;
    protected array  $with     = ['parent:id,name'];
    protected array  $searchable = ['name'];
    protected array  $filterable = ['status', 'parent_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Category::where('company_id', $cid);
        return response()->json([
            'total'  => $q->count(),
            'active' => $q->clone()->where('status', 'active')->count(),
        ]);
    }
}
