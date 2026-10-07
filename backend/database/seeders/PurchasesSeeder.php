<?php

namespace Database\Seeders;

use App\Models\AccountPayable;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PurchasesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $admin   = User::where('email', 'admin@andespeople.co')->first();

        if (! $company || ! $admin) {
            return;
        }

        $wh1 = Warehouse::where('company_id', $company->id)->where('name', 'Bodega Principal')->first();

        if (! $wh1) {
            return; // InventorySeeder debe correr antes
        }

        // ── Proveedores ───────────────────────────────────────────────────────
        $supplierData = [
            ['name' => 'Tech Distribuciones S.A.',       'nit' => '800.111.222-5', 'email' => 'ventas@techdist.co',     'phone' => '+57 601 400 1100', 'terms' => 30],
            ['name' => 'Suministros Oficina Total Ltda', 'nit' => '900.222.333-8', 'email' => 'pedidos@oficina360.co',  'phone' => '+57 601 500 2200', 'terms' => 45],
            ['name' => 'Software Factory S.A.S',          'nit' => '901.333.444-2', 'email' => 'licencias@softfact.co', 'phone' => '+57 315 600 3300', 'terms' => 15],
            ['name' => 'Importaciones Tek Global',        'nit' => '800.444.555-9', 'email' => 'compras@tekglobal.co',  'phone' => '+57 602 700 4400', 'terms' => 60],
        ];

        $suppliers = collect($supplierData)->map(fn ($d) => Supplier::firstOrCreate(
            ['company_id' => $company->id, 'nit' => $d['nit']],
            [
                'name'          => $d['name'],
                'email'         => $d['email'],
                'phone'         => $d['phone'],
                'payment_terms' => $d['terms'],
                'status'        => 'active',
            ]
        ));

        $prod0 = Product::where('company_id', $company->id)->where('sku', 'LP-RRHH-001')->first();
        $prod1 = Product::where('company_id', $company->id)->where('sku', 'LP-RRHH-002')->first();
        $prod2 = Product::where('company_id', $company->id)->where('sku', 'TEK-PC-010')->first();
        $prod3 = Product::where('company_id', $company->id)->where('sku', 'TEK-MOUSE-01')->first();
        $prod4 = Product::where('company_id', $company->id)->where('sku', 'GEN-PAP-100')->first();
        $prod5 = Product::where('company_id', $company->id)->where('sku', 'GEN-TONER-01')->first();

        if (! $prod0 || ! $prod2) {
            return; // InventorySeeder debe correr antes
        }

        // ── OC 1 — recibida y facturada ───────────────────────────────────────
        $oc1 = PurchaseOrder::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OC-2026-0001'],
            [
                'supplier_id'   => $suppliers[0]->id,
                'warehouse_id'  => $wh1->id,
                'user_id'       => $admin->id,
                'date'          => Carbon::today()->subDays(45),
                'expected_date' => Carbon::today()->subDays(30),
                'status'        => 'received',
                'subtotal'      => 22_500_000,
                'tax'           =>  4_275_000,
                'total'         => 26_775_000,
                'notes'         => 'Licencias RRHH semestre 1.',
            ]
        );

        if ($oc1->wasRecentlyCreated) {
            PurchaseOrderItem::create(['purchase_order_id' => $oc1->id, 'product_id' => $prod0->id, 'quantity' => 10, 'unit_cost' => 1_500_000, 'subtotal' => 15_000_000, 'received_qty' => 10]);
            PurchaseOrderItem::create(['purchase_order_id' => $oc1->id, 'product_id' => $prod1->id, 'quantity' => 10, 'unit_cost' =>   750_000, 'subtotal' =>  7_500_000, 'received_qty' => 10]);

            $rc1 = PurchaseReceipt::create([
                'company_id'        => $company->id,
                'purchase_order_id' => $oc1->id,
                'supplier_id'       => $suppliers[0]->id,
                'warehouse_id'      => $wh1->id,
                'user_id'           => $admin->id,
                'number'            => 'REC-2026-0001',
                'date'              => Carbon::today()->subDays(30),
                'type'              => 'receipt',
                'status'            => 'posted',
                'notes'             => 'Recepcion completa OC-2026-0001.',
            ]);
            PurchaseReceiptItem::create(['purchase_receipt_id' => $rc1->id, 'product_id' => $prod0->id, 'quantity' => 10, 'unit_cost' => 1_500_000]);
            PurchaseReceiptItem::create(['purchase_receipt_id' => $rc1->id, 'product_id' => $prod1->id, 'quantity' => 10, 'unit_cost' =>   750_000]);

            $inv1 = PurchaseInvoice::create([
                'company_id'          => $company->id,
                'supplier_id'         => $suppliers[0]->id,
                'purchase_order_id'   => $oc1->id,
                'purchase_receipt_id' => $rc1->id,
                'user_id'             => $admin->id,
                'number'              => 'FCP-2026-0001',
                'date'                => Carbon::today()->subDays(28),
                'due_date'            => Carbon::today()->addDays(2),
                'subtotal'            => 22_500_000,
                'tax'                 =>  4_275_000,
                'total'               => 26_775_000,
                'status'              => 'posted',
            ]);

            AccountPayable::create([
                'company_id'          => $company->id,
                'supplier_id'         => $suppliers[0]->id,
                'purchase_invoice_id' => $inv1->id,
                'amount'              => 26_775_000,
                'balance'             => 26_775_000,
                'due_date'            => Carbon::today()->addDays(2),
                'status'              => 'open',
            ]);
        }

        // ── OC 2 — enviada, pendiente recepcion ───────────────────────────────
        $oc2 = PurchaseOrder::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OC-2026-0002'],
            [
                'supplier_id'   => $suppliers[3]->id,
                'warehouse_id'  => $wh1->id,
                'user_id'       => $admin->id,
                'date'          => Carbon::today()->subDays(10),
                'expected_date' => Carbon::today()->addDays(5),
                'status'        => 'sent',
                'subtotal'      => 38_640_000,
                'tax'           =>  7_341_600,
                'total'         => 45_981_600,
                'notes'         => 'Equipos de computo.',
            ]
        );

        if ($oc2->wasRecentlyCreated) {
            PurchaseOrderItem::create(['purchase_order_id' => $oc2->id, 'product_id' => $prod2->id, 'quantity' => 12, 'unit_cost' => 2_400_000, 'subtotal' => 28_800_000, 'received_qty' => 0]);
            PurchaseOrderItem::create(['purchase_order_id' => $oc2->id, 'product_id' => $prod3->id, 'quantity' => 48, 'unit_cost' =>    65_000, 'subtotal' =>  3_120_000, 'received_qty' => 0]);
        }

        // ── OC 3 — borrador ───────────────────────────────────────────────────
        $oc3 = PurchaseOrder::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OC-2026-0003'],
            [
                'supplier_id'   => $suppliers[1]->id,
                'warehouse_id'  => $wh1->id,
                'user_id'       => $admin->id,
                'date'          => Carbon::today()->subDays(3),
                'expected_date' => Carbon::today()->addDays(15),
                'status'        => 'draft',
                'subtotal'      => 4_200_000,
                'tax'           =>   798_000,
                'total'         => 4_998_000,
                'notes'         => 'Consumibles trimestrales.',
            ]
        );

        if ($oc3->wasRecentlyCreated) {
            PurchaseOrderItem::create(['purchase_order_id' => $oc3->id, 'product_id' => $prod4->id, 'quantity' => 100, 'unit_cost' =>  15_000, 'subtotal' =>  1_500_000, 'received_qty' => 0]);
            PurchaseOrderItem::create(['purchase_order_id' => $oc3->id, 'product_id' => $prod5->id, 'quantity' =>  15, 'unit_cost' => 180_000, 'subtotal' =>  2_700_000, 'received_qty' => 0]);
        }
    }
}
