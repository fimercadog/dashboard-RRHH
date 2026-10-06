<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidProductTypeException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockService
{
    /**
     * Entrada de stock (compra, recepcion, apertura inicial).
     * Actualiza avg_cost con costo promedio ponderado.
     */
    public function entry(
        Product $product,
        Warehouse $warehouse,
        float $qty,
        float $unit_cost,
        string $reference_type = 'manual',
        ?int $reference_id = null,
        ?string $notes = null,
        ?int $user_id = null
    ): StockMovement {
        $this->assertHasStock($product);

        return DB::transaction(function () use ($product, $warehouse, $qty, $unit_cost, $reference_type, $reference_id, $notes, $user_id) {
            $stock = $this->stockRow($product, $warehouse);

            $prev_qty  = (float) $stock->quantity;
            $prev_cost = (float) ($stock->avg_cost ?? $unit_cost);

            $stock->quantity = $prev_qty + $qty;

            if ($qty > 0 && $unit_cost > 0) {
                // Costo promedio ponderado: solo se recalcula en entradas.
                $stock->avg_cost = $stock->quantity > 0
                    ? ($prev_qty * $prev_cost + $qty * $unit_cost) / $stock->quantity
                    : $unit_cost;
            }

            $stock->save();

            return StockMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'type'           => 'entry',
                'quantity'       => $qty,
                'unit_cost'      => $unit_cost,
                'total_cost'     => $qty * $unit_cost,
                'reference_type' => $reference_type,
                'reference_id'   => $reference_id,
                'notes'          => $notes,
                'user_id'        => $user_id ?? auth()->id(),
            ]);
        }, attempts: 3);
    }

    /**
     * Salida de stock (venta, consumo interno).
     * Usa avg_cost vigente como unit_cost del movimiento (COGS).
     * Lanza InsufficientStockException si no hay suficiente stock.
     */
    public function exit(
        Product $product,
        Warehouse $warehouse,
        float $qty,
        string $reference_type = 'manual',
        ?int $reference_id = null,
        ?string $notes = null,
        ?int $user_id = null
    ): StockMovement {
        $this->assertHasStock($product);

        return DB::transaction(function () use ($product, $warehouse, $qty, $reference_type, $reference_id, $notes, $user_id) {
            $stock = $this->stockRow($product, $warehouse);

            if ((float) $stock->quantity < $qty) {
                throw new InsufficientStockException($product, (float) $stock->quantity, $qty);
            }

            $unit_cost = (float) ($stock->avg_cost ?? $product->cost_price);
            $stock->quantity = (float) $stock->quantity - $qty;
            $stock->save();

            return StockMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'type'           => 'exit',
                'quantity'       => $qty,
                'unit_cost'      => $unit_cost,
                'total_cost'     => $qty * $unit_cost,
                'reference_type' => $reference_type,
                'reference_id'   => $reference_id,
                'notes'          => $notes,
                'user_id'        => $user_id ?? auth()->id(),
            ]);
        }, attempts: 3);
    }

    /**
     * Ajuste de inventario: establece el stock a un valor absoluto.
     * Crea un movimiento 'adjustment' de entrada o salida por la diferencia.
     */
    public function adjust(
        Product $product,
        Warehouse $warehouse,
        float $new_qty,
        string $reason,
        ?int $user_id = null
    ): StockMovement {
        $this->assertHasStock($product);

        return DB::transaction(function () use ($product, $warehouse, $new_qty, $reason, $user_id) {
            $stock = $this->stockRow($product, $warehouse);
            $current = (float) $stock->quantity;
            $diff    = $new_qty - $current;

            if ($diff === 0.0) {
                // No hay diferencia: crea un movimiento de apertura de todos modos para la trazabilidad.
                $diff = 0;
            }

            $unit_cost = (float) ($stock->avg_cost ?? $product->cost_price);

            if ($diff > 0) {
                $stock->quantity = $new_qty;
                // No se recalcula avg_cost en ajustes al alza; se conserva el existente.
                $stock->save();
                $type = 'adjustment';
                $qty_mov = $diff;
            } else {
                if ($current + $diff < 0) {
                    throw new InsufficientStockException($product, $current, abs($diff));
                }
                $stock->quantity = $new_qty;
                $stock->save();
                $type = 'adjustment';
                $qty_mov = abs($diff);
            }

            return StockMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'type'           => $type,
                'quantity'       => $qty_mov,
                'unit_cost'      => $unit_cost,
                'total_cost'     => $qty_mov * $unit_cost,
                'reference_type' => 'adjustment',
                'notes'          => $reason,
                'user_id'        => $user_id ?? auth()->id(),
            ]);
        }, attempts: 3);
    }

    /**
     * Traslado entre bodegas.
     * Genera dos movimientos vinculados por el mismo transfer_id (UUID).
     * Atomico: si falla cualquiera de los dos, ambos hacen rollback.
     */
    public function transfer(
        Product $product,
        Warehouse $from,
        Warehouse $to,
        float $qty,
        ?string $notes = null,
        ?int $user_id = null
    ): array {
        $this->assertHasStock($product);

        return DB::transaction(function () use ($product, $from, $to, $qty, $notes, $user_id) {
            $transfer_id = Str::uuid()->toString();
            $uid         = $user_id ?? auth()->id();

            // Salida de la bodega de origen.
            $fromStock = $this->stockRow($product, $from);
            if ((float) $fromStock->quantity < $qty) {
                throw new InsufficientStockException($product, (float) $fromStock->quantity, $qty);
            }
            $unit_cost = (float) ($fromStock->avg_cost ?? $product->cost_price);
            $fromStock->quantity = (float) $fromStock->quantity - $qty;
            $fromStock->save();

            $out = StockMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $from->id,
                'type'           => 'transfer_out',
                'quantity'       => $qty,
                'unit_cost'      => $unit_cost,
                'total_cost'     => $qty * $unit_cost,
                'transfer_id'    => $transfer_id,
                'reference_type' => 'transfer',
                'notes'          => $notes,
                'user_id'        => $uid,
            ]);

            // Entrada en la bodega de destino.
            $toStock = $this->stockRow($product, $to);
            $prev_qty  = (float) $toStock->quantity;
            $prev_cost = (float) ($toStock->avg_cost ?? $unit_cost);
            $toStock->quantity = $prev_qty + $qty;
            $toStock->avg_cost = $toStock->quantity > 0
                ? ($prev_qty * $prev_cost + $qty * $unit_cost) / $toStock->quantity
                : $unit_cost;
            $toStock->save();

            $in = StockMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $to->id,
                'type'           => 'transfer_in',
                'quantity'       => $qty,
                'unit_cost'      => $unit_cost,
                'total_cost'     => $qty * $unit_cost,
                'transfer_id'    => $transfer_id,
                'reference_type' => 'transfer',
                'notes'          => $notes,
                'user_id'        => $uid,
            ]);

            return [$out, $in];
        }, attempts: 3);
    }

    /** Lee o inicializa la fila de stock para producto × bodega. */
    private function stockRow(Product $product, Warehouse $warehouse): ProductStock
    {
        return ProductStock::firstOrNew([
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
        ], [
            'company_id' => $product->company_id,
            'quantity'   => 0,
            'avg_cost'   => null,
        ]);
    }

    /** Los productos de tipo 'service' no tienen stock físico. */
    private function assertHasStock(Product $product): void
    {
        if ($product->type === 'service') {
            throw new InvalidProductTypeException($product);
        }
    }
}
