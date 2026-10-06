<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidProductTypeException;
use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\StockService;
use App\Services\TableQueryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockMovementController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request, TableQueryService $tables)
    {
        $query = StockMovement::query()
            ->where('company_id', $this->companyId($request))
            ->with(['product:id,sku,name', 'warehouse:id,name', 'user:id,name']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $query->latest();

        return StockMovementResource::collection(
            $query->paginate(min((int) $request->input('per_page', 25), 200))
        );
    }

    /** Crear movimientos manuales: entry, exit, adjust, transfer. */
    public function store(Request $request, StockService $stock, AuditService $audit)
    {
        $companyId = $this->companyId($request);

        $data = $request->validate([
            'action'       => ['required', Rule::in(['entry', 'exit', 'adjust', 'transfer'])],
            'product_id'   => ['required', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'quantity'     => ['required', 'numeric', 'min:0.0001'],
            'unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'to_warehouse_id' => ['required_if:action,transfer', 'nullable', 'integer'],
            'new_qty'      => ['required_if:action,adjust', 'nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $product   = Product::where('company_id', $companyId)->findOrFail($data['product_id']);
        $warehouse = Warehouse::where('company_id', $companyId)->findOrFail($data['warehouse_id']);

        try {
            $result = match ($data['action']) {
                'entry' => $stock->entry(
                    $product, $warehouse,
                    (float) $data['quantity'],
                    (float) ($data['unit_cost'] ?? $product->cost_price),
                    notes: $data['notes'] ?? null,
                ),
                'exit' => $stock->exit(
                    $product, $warehouse,
                    (float) $data['quantity'],
                    notes: $data['notes'] ?? null,
                ),
                'adjust' => $stock->adjust(
                    $product, $warehouse,
                    (float) $data['new_qty'],
                    $data['notes'] ?? 'Ajuste manual',
                ),
                'transfer' => $stock->transfer(
                    $product, $warehouse,
                    Warehouse::where('company_id', $companyId)->findOrFail($data['to_warehouse_id']),
                    (float) $data['quantity'],
                    $data['notes'] ?? null,
                ),
            };
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (InvalidProductTypeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Para transfer devuelve array de dos movimientos; auditamos el primero.
        $primary = is_array($result) ? $result[0] : $result;
        $audit->record('created', $primary, $request);

        $resource = is_array($result)
            ? StockMovementResource::collection(collect($result))
            : new StockMovementResource($result->load(['product:id,sku,name', 'warehouse:id,name', 'user:id,name']));

        return $resource->response()->setStatusCode(201);
    }
}
