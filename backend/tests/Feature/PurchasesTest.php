<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchasesTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Warehouse $wh;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);

        $this->company  = Company::factory()->create(['name' => 'Test Compras SA']);
        $this->user     = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::firstOrCreate(['name' => 'Admin Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['purchases.manage', 'purchases.view']);
        $this->user->assignRole($role);

        $this->wh       = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Principal', 'status' => 'active']);
        $this->supplier = Supplier::create(['company_id' => $this->company->id, 'name' => 'Proveedor SA', 'status' => 'active']);
        $this->product  = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Artículo test', 'type' => 'storable',
            'cost_price' => 5000, 'sale_price' => 8000, 'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    // ── 1. CRUD Proveedores ───────────────────────────────────────────────────

    public function test_can_list_suppliers(): void
    {
        $this->getJson('/api/suppliers')->assertOk();
    }

    public function test_can_create_supplier(): void
    {
        $this->postJson('/api/suppliers', [
            'name'   => 'Nuevo Proveedor',
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.name', 'Nuevo Proveedor');
    }

    // ── 2. Crear Orden de Compra ─────────────────────────────────────────────

    public function test_can_create_purchase_order(): void
    {
        $resp = $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 5000],
            ],
        ])->assertCreated();

        $this->assertEquals(50000, $resp->json('data.total'));
    }

    // ── 3. Crear Recepción ───────────────────────────────────────────────────

    public function test_can_create_receipt(): void
    {
        $this->postJson('/api/purchase-receipts', [
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'type'         => 'receipt',
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 5000],
            ],
        ])->assertCreated();
    }

    // ── 4. Postear Recepción genera StockMovement ────────────────────────────

    public function test_post_receipt_increases_stock(): void
    {
        $receipt = PurchaseReceipt::create([
            'company_id'   => $this->company->id,
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'user_id'      => $this->user->id,
            'number'       => 'RC-001',
            'date'         => now(),
            'type'         => 'receipt',
            'status'       => 'draft',
        ]);
        PurchaseReceiptItem::create([
            'purchase_receipt_id' => $receipt->id,
            'product_id'          => $this->product->id,
            'quantity'            => 8,
            'unit_cost'           => 5000,
        ]);

        $this->postJson("/api/purchase-receipts/{$receipt->id}/post")->assertOk();

        $stock = ProductStock::where('product_id', $this->product->id)->where('warehouse_id', $this->wh->id)->first();
        $this->assertEquals(8, $stock->quantity);
    }

    // ── 5. Doble posteo falla ────────────────────────────────────────────────

    public function test_double_post_receipt_returns_422(): void
    {
        $receipt = PurchaseReceipt::create([
            'company_id'   => $this->company->id,
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'user_id'      => $this->user->id,
            'number'       => 'RC-002',
            'date'         => now(),
            'type'         => 'receipt',
            'status'       => 'posted',
        ]);

        $this->postJson("/api/purchase-receipts/{$receipt->id}/post")->assertStatus(422);
    }

    // ── 6. Crear Factura de Compra ───────────────────────────────────────────

    public function test_can_create_purchase_invoice(): void
    {
        $this->postJson('/api/purchase-invoices', [
            'supplier_id' => $this->supplier->id,
            'number'      => 'FV-001',
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(30)->toDateString(),
            'subtotal'    => 50000,
            'total'       => 55000,
        ])->assertCreated();
    }

    // ── 7. Postear Factura crea CxP ──────────────────────────────────────────

    public function test_post_invoice_creates_account_payable(): void
    {
        $invoice = PurchaseInvoice::create([
            'company_id'  => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'user_id'     => $this->user->id,
            'number'      => 'FV-002',
            'date'        => now(),
            'due_date'    => now()->addDays(30),
            'subtotal'    => 50000,
            'tax'         => 9500,
            'total'       => 59500,
            'status'      => 'draft',
        ]);

        $this->postJson("/api/purchase-invoices/{$invoice->id}/post")->assertOk();

        $cxp = AccountPayable::where('purchase_invoice_id', $invoice->id)->first();
        $this->assertNotNull($cxp);
        $this->assertEquals(59500, $cxp->amount);
        $this->assertEquals('open', $cxp->status);
    }

    // ── Validación 422 ──────────────────────────────────────────────────────

    public function test_create_order_without_supplier_returns_422(): void
    {
        $this->postJson('/api/purchase-orders', [
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => 1000]],
        ])->assertStatus(422)->assertJsonValidationErrors('supplier_id');
    }

    public function test_create_order_without_items_returns_422(): void
    {
        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'items'        => [],
        ])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_create_order_with_invalid_quantity_returns_422(): void
    {
        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $this->product->id, 'quantity' => 0, 'unit_cost' => 1000]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');
    }

    public function test_create_receipt_without_supplier_returns_422(): void
    {
        $this->postJson('/api/purchase-receipts', [
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'type'         => 'receipt',
            'items'        => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => 1000]],
        ])->assertStatus(422)->assertJsonValidationErrors('supplier_id');
    }

    public function test_purchases_require_permission(): void
    {
        $guest = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($guest, 'sanctum');

        $this->postJson('/api/purchase-orders', [
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'items'        => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => 1000]],
        ])->assertStatus(403);
    }

    // ── 8. Multitenancy: no ve recursos de otra empresa ─────────────────────

    public function test_cannot_see_other_company_supplier(): void
    {
        $other = Company::factory()->create(['name' => 'Otra Empresa']);
        Supplier::create(['company_id' => $other->id, 'name' => 'Otro proveedor', 'status' => 'active']);

        $resp = $this->getJson('/api/suppliers');
        $names = collect($resp->json('data'))->pluck('name');
        $this->assertNotContains('Otro proveedor', $names);
    }
}
