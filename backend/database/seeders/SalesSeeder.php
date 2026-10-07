<?php

namespace Database\Seeders;

use App\Models\AccountsReceivable;
use App\Models\Client;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SalesSeeder extends Seeder
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

        // ── Clientes de CrmSeeder ─────────────────────────────────────────────
        $c0 = Client::where('company_id', $company->id)->where('identification_number', '900.123.456-7')->first();
        $c1 = Client::where('company_id', $company->id)->where('identification_number', '800.456.789-2')->first();
        $c2 = Client::where('company_id', $company->id)->where('identification_number', '1035012345')->first();

        if (! $c0) {
            return; // CrmSeeder debe correr antes
        }

        $deal0 = Deal::where('company_id', $company->id)->where('client_id', $c0->id)->where('stage', 'proposal')->first();
        $deal2 = $c2 ? Deal::where('company_id', $company->id)->where('client_id', $c2->id)->where('stage', 'won')->first() : null;

        // ── Productos de InventorySeeder ──────────────────────────────────────
        $prod0 = Product::where('company_id', $company->id)->where('sku', 'LP-RRHH-001')->first();
        $prod1 = Product::where('company_id', $company->id)->where('sku', 'LP-RRHH-002')->first();
        $prod7 = Product::where('company_id', $company->id)->where('sku', 'DFC-CONS-001')->first();
        $prod9 = Product::where('company_id', $company->id)->where('sku', 'DFC-TRAIN-01')->first();

        if (! $prod0) {
            return; // InventorySeeder debe correr antes
        }

        $c1ref = $c1 ?? $c0;
        $c2ref = $c2 ?? $c0;

        // ── Cotización 1 — aceptada (Grupo Andino) ───────────────────────────
        $q1 = Quote::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'COT-2026-0001'],
            [
                'client_id'      => $c0->id,
                'deal_id'        => $deal0 ? $deal0->id : null,
                'user_id'        => $admin->id,
                'date'           => Carbon::today()->subDays(30),
                'valid_until'    => Carbon::today()->subDays(20),
                'status'         => 'accepted',
                'subtotal'       => 58_000_000,
                'discount_total' => 0,
                'tax'            => 11_020_000,
                'total'          => 69_020_000,
                'notes'          => 'Propuesta implementacion plataforma RRHH Grupo Andino.',
                'client_uuid'    => Str::uuid(),
            ]
        );

        if ($q1->wasRecentlyCreated) {
            QuoteItem::create(['quote_id' => $q1->id, 'product_id' => $prod0->id, 'quantity' => 20, 'unit_price' => 2_000_000, 'discount_pct' => 0, 'subtotal' => 40_000_000]);
            QuoteItem::create(['quote_id' => $q1->id, 'product_id' => $prod7->id, 'quantity' => 100, 'unit_price' => 180_000, 'discount_pct' => 0, 'subtotal' => 18_000_000]);
        }

        // ── Cotización 2 — enviada (Constructora Pacifico) ───────────────────
        $q2 = Quote::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'COT-2026-0002'],
            [
                'client_id'      => $c1ref->id,
                'deal_id'        => null,
                'user_id'        => $admin->id,
                'date'           => Carbon::today()->subDays(5),
                'valid_until'    => Carbon::today()->addDays(25),
                'status'         => 'sent',
                'subtotal'       => 10_450_000,
                'discount_total' => 0,
                'tax'            =>  1_985_500,
                'total'          => 12_435_500,
                'notes'          => 'Capacitacion y licencias ERP Constructora Pacifico.',
                'client_uuid'    => Str::uuid(),
            ]
        );

        if ($q2->wasRecentlyCreated) {
            QuoteItem::create(['quote_id' => $q2->id, 'product_id' => $prod9->id, 'quantity' => 8, 'unit_price' => 650_000, 'discount_pct' => 0, 'subtotal' => 5_200_000]);
            QuoteItem::create(['quote_id' => $q2->id, 'product_id' => $prod1->id, 'quantity' => 7, 'unit_price' => 750_000, 'discount_pct' => 0, 'subtotal' => 5_250_000]);
        }

        // ── Cotización 3 — aceptada (Hernan Giraldo) ─────────────────────────
        $q3 = Quote::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'COT-2026-0003'],
            [
                'client_id'      => $c2ref->id,
                'deal_id'        => $deal2 ? $deal2->id : null,
                'user_id'        => $admin->id,
                'date'           => Carbon::today()->subDays(35),
                'valid_until'    => Carbon::today()->subDays(5),
                'status'         => 'accepted',
                'subtotal'       => 7_142_857,
                'discount_total' => 0,
                'tax'            => 1_357_143,
                'total'          => 8_500_000,
                'notes'          => 'Licencia anual plataforma freelancer — cliente natural.',
                'client_uuid'    => Str::uuid(),
            ]
        );

        if ($q3->wasRecentlyCreated) {
            QuoteItem::create(['quote_id' => $q3->id, 'product_id' => $prod0->id, 'quantity' => 1, 'unit_price' => 2_800_000, 'discount_pct' => 0, 'subtotal' => 2_800_000]);
            QuoteItem::create(['quote_id' => $q3->id, 'product_id' => $prod7->id, 'quantity' => 24, 'unit_price' => 180_000, 'discount_pct' => 0, 'subtotal' => 4_320_000]);
        }

        // ── Orden de venta 1 — fulfilled (de COT-2026-0001) ──────────────────
        $so1 = SaleOrder::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OV-2026-0001'],
            [
                'client_id'      => $c0->id,
                'quote_id'       => $q1->id,
                'user_id'        => $admin->id,
                'warehouse_id'   => $wh1->id,
                'date'           => Carbon::today()->subDays(25),
                'status'         => 'fulfilled',
                'subtotal'       => 58_000_000,
                'discount_total' => 0,
                'tax'            => 11_020_000,
                'total'          => 69_020_000,
            ]
        );

        if ($so1->wasRecentlyCreated) {
            SaleOrderItem::create(['sale_order_id' => $so1->id, 'product_id' => $prod0->id, 'quantity' => 20, 'unit_price' => 2_000_000, 'discount_pct' => 0, 'subtotal' => 40_000_000, 'delivered_qty' => 20]);
            SaleOrderItem::create(['sale_order_id' => $so1->id, 'product_id' => $prod7->id, 'quantity' => 100, 'unit_price' => 180_000, 'discount_pct' => 0, 'subtotal' => 18_000_000, 'delivered_qty' => 100]);
        }

        // ── Orden de venta 2 — confirmed (de COT-2026-0003) ──────────────────
        $so2 = SaleOrder::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'OV-2026-0002'],
            [
                'client_id'      => $c2ref->id,
                'quote_id'       => $q3->id,
                'user_id'        => $admin->id,
                'warehouse_id'   => $wh1->id,
                'date'           => Carbon::today()->subDays(30),
                'status'         => 'confirmed',
                'subtotal'       => 7_142_857,
                'discount_total' => 0,
                'tax'            => 1_357_143,
                'total'          => 8_500_000,
            ]
        );

        if ($so2->wasRecentlyCreated) {
            SaleOrderItem::create(['sale_order_id' => $so2->id, 'product_id' => $prod0->id, 'quantity' => 1, 'unit_price' => 2_800_000, 'discount_pct' => 0, 'subtotal' => 2_800_000, 'delivered_qty' => 0]);
            SaleOrderItem::create(['sale_order_id' => $so2->id, 'product_id' => $prod7->id, 'quantity' => 24, 'unit_price' => 180_000, 'discount_pct' => 0, 'subtotal' => 4_320_000, 'delivered_qty' => 0]);
        }

        // ── Factura de venta 1 — posted, CxC abierta ─────────────────────────
        $fi1 = SaleInvoice::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'FEV-2026-0001'],
            [
                'client_id'      => $c0->id,
                'sale_order_id'  => $so1->id,
                'user_id'        => $admin->id,
                'date'           => Carbon::today()->subDays(22),
                'due_date'       => Carbon::today()->addDays(8),
                'type'           => 'invoice',
                'status'         => 'posted',
                'subtotal'       => 58_000_000,
                'discount_total' => 0,
                'tax'            => 11_020_000,
                'total'          => 69_020_000,
            ]
        );

        if ($fi1->wasRecentlyCreated) {
            SaleInvoiceItem::create(['sale_invoice_id' => $fi1->id, 'product_id' => $prod0->id, 'quantity' => 20, 'unit_price' => 2_000_000, 'discount_pct' => 0, 'subtotal' => 40_000_000, 'cost_at_time' => 1_500_000]);
            SaleInvoiceItem::create(['sale_invoice_id' => $fi1->id, 'product_id' => $prod7->id, 'quantity' => 100, 'unit_price' => 180_000, 'discount_pct' => 0, 'subtotal' => 18_000_000, 'cost_at_time' => 80_000]);

            AccountsReceivable::create([
                'company_id'      => $company->id,
                'client_id'       => $c0->id,
                'sale_invoice_id' => $fi1->id,
                'amount'          => 69_020_000,
                'balance'         => 69_020_000,
                'due_date'        => Carbon::today()->addDays(8),
                'status'          => 'open',
            ]);
        }

        // ── Factura de venta 2 — posted, CxC parcial ─────────────────────────
        $fi2 = SaleInvoice::firstOrCreate(
            ['company_id' => $company->id, 'number' => 'FEV-2026-0002'],
            [
                'client_id'      => $c2ref->id,
                'sale_order_id'  => $so2->id,
                'user_id'        => $admin->id,
                'date'           => Carbon::today()->subDays(28),
                'due_date'       => Carbon::today()->subDays(5),
                'type'           => 'invoice',
                'status'         => 'partially_paid',
                'subtotal'       => 7_142_857,
                'discount_total' => 0,
                'tax'            => 1_357_143,
                'total'          => 8_500_000,
            ]
        );

        if ($fi2->wasRecentlyCreated) {
            SaleInvoiceItem::create(['sale_invoice_id' => $fi2->id, 'product_id' => $prod0->id, 'quantity' => 1, 'unit_price' => 2_800_000, 'discount_pct' => 0, 'subtotal' => 2_800_000, 'cost_at_time' => 1_500_000]);
            SaleInvoiceItem::create(['sale_invoice_id' => $fi2->id, 'product_id' => $prod7->id, 'quantity' => 24, 'unit_price' => 180_000, 'discount_pct' => 0, 'subtotal' => 4_320_000, 'cost_at_time' => 80_000]);

            AccountsReceivable::create([
                'company_id'      => $company->id,
                'client_id'       => $c2ref->id,
                'sale_invoice_id' => $fi2->id,
                'amount'          => 8_500_000,
                'balance'         => 4_250_000,
                'due_date'        => Carbon::today()->subDays(5),
                'status'          => 'partially_paid',
            ]);
        }
    }
}
