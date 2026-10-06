<?php

namespace App\Services;

use App\Models\AccountsReceivable;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Models\SaleOrder;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private StockService      $stock,
        private AccountingService $accounting,
    ) {}

    /**
     * Confirma un pedido de venta (draft → confirmed).
     */
    public function confirmOrder(SaleOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw new \LogicException("El pedido ya fue confirmado o cancelado.");
        }

        $order->update(['status' => 'confirmed']);
    }

    /**
     * Postea una factura de venta:
     * - Por cada ítem físico: StockService::exit + captura cost_at_time
     * - Crea AccountsReceivable
     * - Marca factura como posted
     */
    public function postInvoice(SaleInvoice $invoice, int $userId): void
    {
        if ($invoice->status !== 'draft') {
            throw new \LogicException("La factura ya fue posteada o cancelada.");
        }

        $invoice->loadMissing(['items.product', 'saleOrder']);
        $warehouseId = $invoice->saleOrder?->warehouse_id;

        DB::transaction(function () use ($invoice, $warehouseId, $userId) {
            $invoice->items->each(function (SaleInvoiceItem $item) use ($invoice, $warehouseId, $userId) {
                $product = Product::where('company_id', $invoice->company_id)->findOrFail($item->product_id);

                if ($product->type !== 'service' && $warehouseId) {
                    $warehouse = Warehouse::findOrFail($warehouseId);
                    $movement  = $this->stock->exit(
                        $product,
                        $warehouse,
                        (float) $item->quantity,
                        'sale_invoice',
                        $invoice->id,
                        user_id: $userId,
                    );
                    // Captura cost_at_time del avg_cost usado en la salida (inmutable)
                    $item->update(['cost_at_time' => $movement->unit_cost]);
                }
            });

            AccountsReceivable::create([
                'company_id'      => $invoice->company_id,
                'client_id'       => $invoice->client_id,
                'sale_invoice_id' => $invoice->id,
                'amount'          => $invoice->total,
                'balance'         => $invoice->total,
                'due_date'        => $invoice->due_date,
                'status'          => 'open',
            ]);

            $invoice->update(['status' => 'posted']);
        });

        // Contabilizar factura de venta (silencioso si no hay período abierto)
        $this->accounting->generateAndPost('sale_invoice', $invoice->id, $invoice->company_id, $userId);
    }

    /**
     * Crea una nota de devolución (factura tipo 'return') desde una factura posteada.
     * Devuelve el stock y crea una CxC con monto negativo (crédito al cliente).
     */
    public function createReturn(SaleInvoice $original, int $userId): SaleInvoice
    {
        if ($original->status !== 'posted') {
            throw new \LogicException("Solo se pueden devolver facturas posteadas.");
        }

        $original->loadMissing(['items.product', 'saleOrder']);
        $warehouseId = $original->saleOrder?->warehouse_id;

        return DB::transaction(function () use ($original, $warehouseId, $userId) {
            $return = SaleInvoice::create([
                'company_id'     => $original->company_id,
                'client_id'      => $original->client_id,
                'sale_order_id'  => $original->sale_order_id,
                'user_id'        => $userId,
                'number'         => 'NC-'.$original->number,
                'date'           => now()->toDateString(),
                'due_date'       => now()->toDateString(),
                'type'           => 'return',
                'status'         => 'draft',
                'subtotal'       => $original->subtotal,
                'discount_total' => $original->discount_total,
                'tax'            => $original->tax,
                'total'          => $original->total,
                'notes'          => 'Devolución de '.$original->number,
            ]);

            $original->items->each(function (SaleInvoiceItem $item) use ($return, $original, $warehouseId, $userId) {
                $product = Product::where('company_id', $original->company_id)->findOrFail($item->product_id);

                $costAtTime = (float) $item->cost_at_time;

                SaleInvoiceItem::create([
                    'sale_invoice_id' => $return->id,
                    'product_id'      => $item->product_id,
                    'quantity'        => $item->quantity,
                    'unit_price'      => $item->unit_price,
                    'discount_pct'    => $item->discount_pct,
                    'subtotal'        => $item->subtotal,
                    'cost_at_time'    => $costAtTime,
                ]);

                if ($product->type !== 'service' && $warehouseId) {
                    $warehouse = Warehouse::findOrFail($warehouseId);
                    // Entrada de stock por devolución al costo original
                    $this->stock->entry(
                        $product,
                        $warehouse,
                        (float) $item->quantity,
                        $costAtTime ?: (float) ($product->cost_price ?? 0),
                        'sale_return',
                        $return->id,
                        user_id: $userId,
                    );
                }
            });

            // CxC negativa = crédito al cliente
            AccountsReceivable::create([
                'company_id'      => $original->company_id,
                'client_id'       => $original->client_id,
                'sale_invoice_id' => $return->id,
                'amount'          => -$original->total,
                'balance'         => -$original->total,
                'due_date'        => now()->toDateString(),
                'status'          => 'open',
            ]);

            $return->update(['status' => 'posted']);

            return $return->load('items');
        });
    }
}
