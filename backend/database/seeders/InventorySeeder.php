<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $admin   = User::where('email', 'admin@andespeople.co')->first();

        if (! $company || ! $admin) {
            return;
        }

        // ── Categorias ────────────────────────────────────────────────────────
        $catSoftware = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Software'],                    ['status' => 'active']);
        $catHardware = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Hardware'],                    ['status' => 'active']);
        $catSub1     = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Licencias'],                   ['parent_id' => $catSoftware->id, 'status' => 'active']);
        $catSub2     = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Perifericos'],                 ['parent_id' => $catHardware->id, 'status' => 'active']);
        $catConsumo  = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Consumibles'],                 ['status' => 'active']);
        $catServ     = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Servicios profesionales'],     ['status' => 'active']);

        // ── Marcas ────────────────────────────────────────────────────────────
        $brandLP  = Brand::firstOrCreate(['company_id' => $company->id, 'name' => 'LaborPro'],   ['status' => 'active']);
        $brandDFC = Brand::firstOrCreate(['company_id' => $company->id, 'name' => 'DFC'],         ['status' => 'active']);
        $brandGEN = Brand::firstOrCreate(['company_id' => $company->id, 'name' => 'Generica'],    ['status' => 'active']);
        $brandTEK = Brand::firstOrCreate(['company_id' => $company->id, 'name' => 'TekSupplies'], ['status' => 'active']);

        // ── Unidades de medida ────────────────────────────────────────────────
        $unitUnd  = Unit::firstOrCreate(['company_id' => $company->id, 'name' => 'Unidad'],   ['abbreviation' => 'und', 'status' => 'active']);
        $unitLic  = Unit::firstOrCreate(['company_id' => $company->id, 'name' => 'Licencia'], ['abbreviation' => 'lic', 'status' => 'active']);
        $unitHr   = Unit::firstOrCreate(['company_id' => $company->id, 'name' => 'Hora'],     ['abbreviation' => 'hr',  'status' => 'active']);
        $unitPack = Unit::firstOrCreate(['company_id' => $company->id, 'name' => 'Paquete'],  ['abbreviation' => 'pkg', 'status' => 'active']);

        // ── Bodegas ────────────────────────────────────────────────────────────
        $wh1 = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Bodega Principal'],
            ['location' => 'Calle 93 #14-20, Bogota', 'status' => 'active']
        );
        $wh2 = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Bodega Secundaria'],
            ['location' => 'Zona Industrial Puente Aranda, Bogota', 'status' => 'active']
        );

        // ── Productos ─────────────────────────────────────────────────────────
        $productDefs = [
            ['sku' => 'LP-RRHH-001',  'name' => 'Plataforma RRHH — Licencia anual',    'type' => 'storable',   'cost' => 1_500_000, 'price' => 2_800_000, 'min' => 1,  'cat' => $catSub1->id,     'brand' => $brandLP->id,  'unit' => $unitLic->id],
            ['sku' => 'LP-RRHH-002',  'name' => 'Modulo Nomina — Licencia anual',       'type' => 'storable',   'cost' =>   800_000, 'price' => 1_500_000, 'min' => 1,  'cat' => $catSub1->id,     'brand' => $brandLP->id,  'unit' => $unitLic->id],
            ['sku' => 'DFC-ERP-003',  'name' => 'ERP Empresarial — Licencia mensual',  'type' => 'storable',   'cost' =>   600_000, 'price' => 1_200_000, 'min' => 2,  'cat' => $catSub1->id,     'brand' => $brandDFC->id, 'unit' => $unitLic->id],
            ['sku' => 'TEK-PC-010',   'name' => 'Computador portatil 15 pulgadas',     'type' => 'storable',   'cost' => 2_400_000, 'price' => 3_200_000, 'min' => 5,  'cat' => $catHardware->id, 'brand' => $brandTEK->id, 'unit' => $unitUnd->id],
            ['sku' => 'TEK-MOUSE-01', 'name' => 'Mouse inalambrico ergonomico',         'type' => 'storable',   'cost' =>    65_000, 'price' =>   120_000, 'min' => 10, 'cat' => $catSub2->id,     'brand' => $brandTEK->id, 'unit' => $unitUnd->id],
            ['sku' => 'GEN-PAP-100',  'name' => 'Resma papel carta 500 hojas',         'type' => 'consumable', 'cost' =>    15_000, 'price' =>    25_000, 'min' => 50, 'cat' => $catConsumo->id,  'brand' => $brandGEN->id, 'unit' => $unitPack->id],
            ['sku' => 'GEN-TONER-01', 'name' => 'Toner impresora laser negro',          'type' => 'consumable', 'cost' =>   180_000, 'price' =>   280_000, 'min' => 5,  'cat' => $catConsumo->id,  'brand' => $brandGEN->id, 'unit' => $unitUnd->id],
            ['sku' => 'DFC-CONS-001', 'name' => 'Consultoria de implementacion (hora)', 'type' => 'service',    'cost' =>    80_000, 'price' =>   180_000, 'min' => 0,  'cat' => $catServ->id,     'brand' => $brandDFC->id, 'unit' => $unitHr->id],
            ['sku' => 'DFC-CONS-002', 'name' => 'Soporte tecnico por hora',             'type' => 'service',    'cost' =>    50_000, 'price' =>   120_000, 'min' => 0,  'cat' => $catServ->id,     'brand' => $brandDFC->id, 'unit' => $unitHr->id],
            ['sku' => 'DFC-TRAIN-01', 'name' => 'Capacitacion usuarios sesion 4 horas', 'type' => 'service',   'cost' =>   300_000, 'price' =>   650_000, 'min' => 0,  'cat' => $catServ->id,     'brand' => $brandDFC->id, 'unit' => $unitUnd->id],
        ];

        $prods = collect($productDefs)->map(fn ($p) => Product::firstOrCreate(
            ['company_id' => $company->id, 'sku' => $p['sku']],
            [
                'name'        => $p['name'],
                'type'        => $p['type'],
                'cost_price'  => $p['cost'],
                'sale_price'  => $p['price'],
                'min_stock'   => $p['min'],
                'category_id' => $p['cat'],
                'brand_id'    => $p['brand'],
                'unit_id'     => $p['unit'],
                'status'      => 'active',
            ]
        ));

        // ── Stock inicial (solo productos almacenables) ───────────────────────
        // [product_index, warehouse, qty, avg_cost]
        $stockData = [
            [0, $wh1, 15,  1_500_000],
            [1, $wh1, 22,    800_000],
            [2, $wh1, 30,    600_000],
            [3, $wh1, 8,   2_400_000],
            [3, $wh2, 4,   2_400_000],
            [4, $wh1, 35,     65_000],
            [4, $wh2, 20,     65_000],
            [5, $wh1, 120,    15_000],
            [6, $wh1, 18,    180_000],
        ];

        foreach ($stockData as [$idx, $wh, $qty, $cost]) {
            $product = $prods[$idx];

            ProductStock::firstOrCreate(
                ['company_id' => $company->id, 'product_id' => $product->id, 'warehouse_id' => $wh->id],
                ['quantity' => $qty, 'avg_cost' => $cost]
            );

            StockMovement::firstOrCreate(
                ['company_id' => $company->id, 'product_id' => $product->id, 'warehouse_id' => $wh->id, 'type' => 'opening'],
                [
                    'quantity'   => $qty,
                    'unit_cost'  => $cost,
                    'total_cost' => $qty * $cost,
                    'user_id'    => $admin->id,
                    'notes'      => 'Stock de apertura — ingreso inicial del sistema demo.',
                ]
            );
        }
    }
}
