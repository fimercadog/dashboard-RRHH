<?php

namespace App\Services;

use App\Models\AccountPayable;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockService      $stock,
        private AccountingService $accounting,
    ) {}

    /**
     * Postea una recepción: genera un StockMovement de entrada por cada ítem.
     */
    public function postReceipt(PurchaseReceipt $receipt, int $userId): void
    {
        if ($receipt->status !== 'draft') {
            throw new \LogicException("La recepción ya fue posteada o cancelada.");
        }

        $warehouse = Warehouse::findOrFail($receipt->warehouse_id);

        DB::transaction(function () use ($receipt, $warehouse, $userId) {
            $receipt->items()->with('product')->each(function (PurchaseReceiptItem $item) use ($receipt, $warehouse, $userId) {
                $product = Product::where('company_id', $receipt->company_id)->findOrFail($item->product_id);

                if ($product->type !== 'service') {
                    $this->stock->entry(
                        $product,
                        $warehouse,
                        (float) $item->quantity,
                        (float) $item->unit_cost,
                        'purchase_receipt',
                        $receipt->id,
                        user_id: $userId,
                    );
                }
            });

            $receipt->update(['status' => 'posted']);
        });
    }

    /**
     * Postea una factura: crea una CxP y marca la factura como posted.
     */
    public function postInvoice(PurchaseInvoice $invoice, int $userId): void
    {
        if ($invoice->status !== 'draft') {
            throw new \LogicException("La factura ya fue posteada o cancelada.");
        }

        DB::transaction(function () use ($invoice) {
            AccountPayable::create([
                'company_id'         => $invoice->company_id,
                'supplier_id'        => $invoice->supplier_id,
                'purchase_invoice_id'=> $invoice->id,
                'amount'             => $invoice->total,
                'balance'            => $invoice->total,
                'due_date'           => $invoice->due_date,
                'status'             => 'open',
            ]);

            $invoice->update(['status' => 'posted']);
        });

        // Contabilizar factura de compra (silencioso si no hay período abierto)
        $this->accounting->generateAndPost('purchase_invoice', $invoice->id, $invoice->company_id, $userId);
    }
}
