<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductStockResource;
use App\Models\ProductStock;
use App\Services\TableQueryService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request, TableQueryService $tables)
    {
        $query = ProductStock::query()
            ->where('company_id', $this->companyId($request))
            ->with(['product:id,sku,name,min_stock,type', 'warehouse:id,name']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->boolean('low_stock')) {
            // Productos con stock <= min_stock (solo storable/consumable con min_stock definido).
            $query->whereHas('product', fn ($q) => $q->whereNotNull('min_stock')->where('type', '!=', 'service'))
                  ->whereColumn('quantity', '<=', 'products.min_stock')
                  ->join('products', 'products.id', '=', 'product_stock.product_id');
        }

        return ProductStockResource::collection(
            $query->paginate(min((int) $request->input('per_page', 25), 200))
        );
    }
}
