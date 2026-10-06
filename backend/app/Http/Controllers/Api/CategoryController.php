<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends BaseCrudController
{
    protected string $model    = Category::class;
    protected string $resource = CategoryResource::class;
    protected array  $with     = ['parent:id,name'];
    protected array  $searchable = ['name'];
    protected array  $filterable = ['status', 'parent_id'];
}
