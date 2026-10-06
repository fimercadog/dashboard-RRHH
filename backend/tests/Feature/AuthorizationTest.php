<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * K1 — Verifica que los FormRequests rechacen IDs de otra empresa.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Company $other;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['purchases.manage', 'sales.manage', 'inventory.manage'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->company = Company::factory()->create(['name' => 'Empresa A']);
        $this->other   = Company::factory()->create(['name' => 'Empresa B']);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::firstOrCreate(['name' => 'Admin K1', 'guard_name' => 'web']);
        $role->givePermissionTo(['purchases.manage', 'sales.manage', 'inventory.manage']);
        $this->user->assignRole($role);

        $this->actingAs($this->user, 'sanctum');
    }

    // ── 1. FK de otra empresa rechazada en Compras ───────────────────────────

    public function test_purchase_order_rejects_supplier_from_other_company(): void
    {
        $otherSupplier = Supplier::create(['company_id' => $this->other->id, 'name' => 'Proveedor B', 'status' => 'active']);
        $wh = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Bodega', 'status' => 'active']);
        $product = Product::create(['company_id' => $this->company->id, 'name' => 'P', 'type' => 'storable', 'cost_price' => 0, 'sale_price' => 0, 'status' => 'active']);

        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $otherSupplier->id,
            'warehouse_id' => $wh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 100]],
        ])->assertStatus(422)->assertJsonValidationErrors('supplier_id');
    }

    public function test_purchase_order_rejects_warehouse_from_other_company(): void
    {
        $supplier = Supplier::create(['company_id' => $this->company->id, 'name' => 'Prov A', 'status' => 'active']);
        $otherWh  = Warehouse::create(['company_id' => $this->other->id, 'name' => 'Bodega B', 'status' => 'active']);
        $product  = Product::create(['company_id' => $this->company->id, 'name' => 'P', 'type' => 'storable', 'cost_price' => 0, 'sale_price' => 0, 'status' => 'active']);

        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $supplier->id,
            'warehouse_id' => $otherWh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 100]],
        ])->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
    }

    public function test_purchase_order_rejects_product_from_other_company(): void
    {
        $supplier     = Supplier::create(['company_id' => $this->company->id, 'name' => 'Prov A', 'status' => 'active']);
        $wh           = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Bodega', 'status' => 'active']);
        $otherProduct = Product::create(['company_id' => $this->other->id, 'name' => 'P-other', 'type' => 'storable', 'cost_price' => 0, 'sale_price' => 0, 'status' => 'active']);

        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $supplier->id,
            'warehouse_id' => $wh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $otherProduct->id, 'quantity' => 1, 'unit_cost' => 100]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.product_id');
    }

    // ── 2. FK de otra empresa rechazada en Ventas ────────────────────────────

    public function test_quote_rejects_client_from_other_company(): void
    {
        $otherClient = Client::create([
            'company_id' => $this->other->id, 'first_name' => 'X', 'last_name' => 'Y', 'name' => 'XY', 'status' => 'active',
        ]);
        $product = Product::create(['company_id' => $this->company->id, 'name' => 'P', 'type' => 'storable', 'cost_price' => 0, 'sale_price' => 0, 'status' => 'active']);

        $this->postJson('/api/quotes', [
            'client_id'   => $otherClient->id,
            'date'        => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'items'       => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'discount_pct' => 0]],
        ])->assertStatus(422)->assertJsonValidationErrors('client_id');
    }

    // ── 3. IDs propios siguen funcionando ────────────────────────────────────

    public function test_purchase_order_accepts_own_company_resources(): void
    {
        $supplier = Supplier::create(['company_id' => $this->company->id, 'name' => 'Prov A', 'status' => 'active']);
        $wh       = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Bodega', 'status' => 'active']);
        $product  = Product::create(['company_id' => $this->company->id, 'name' => 'P', 'type' => 'storable', 'cost_price' => 0, 'sale_price' => 0, 'status' => 'active']);

        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $supplier->id,
            'warehouse_id' => $wh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 1000]],
        ])->assertCreated();
    }
}
