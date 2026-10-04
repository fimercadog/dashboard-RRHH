<?php

namespace Tests\Feature;

use App\Models\AccountsReceivable;
use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Warehouse $wh;
    private Client $client;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'sales.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sales.view', 'guard_name' => 'web']);

        $this->company = Company::factory()->create(['name' => 'Test Ventas SA']);
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::firstOrCreate(['name' => 'Ventas Test', 'guard_name' => 'web']);
        $role->givePermissionTo(['sales.manage', 'sales.view']);
        $this->user->assignRole($role);

        $this->wh = Warehouse::create([
            'company_id' => $this->company->id,
            'name'       => 'Bodega Principal',
            'status'     => 'active',
        ]);

        $this->client = Client::create([
            'company_id' => $this->company->id,
            'first_name' => 'Cliente',
            'last_name'  => 'Test SA',
            'name'       => 'Cliente Test SA',
            'status'     => 'active',
        ]);

        $this->product = Product::create([
            'company_id'  => $this->company->id,
            'name'        => 'Producto Test',
            'type'        => 'storable',
            'cost_price'  => 5000,
            'sale_price'  => 10000,
            'status'      => 'active',
        ]);

        // Seed initial stock for sale tests
        ProductStock::create([
            'company_id'   => $this->company->id,
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->wh->id,
            'quantity'     => 100,
            'avg_cost'     => 5000,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    // ── 1. CRUD Cotizaciones ─────────────────────────────────────────────────

    public function test_can_list_quotes(): void
    {
        $this->getJson('/api/quotes')->assertOk();
    }

    public function test_can_create_quote(): void
    {
        $resp = $this->postJson('/api/quotes', [
            'client_id'   => $this->client->id,
            'date'        => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'items'       => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 10000, 'discount_pct' => 0],
            ],
        ])->assertCreated();

        $this->assertEquals(30000, $resp->json('data.total'));
    }

    // ── 2. CRUD Pedidos de venta ─────────────────────────────────────────────

    public function test_can_create_sale_order(): void
    {
        $resp = $this->postJson('/api/sale-orders', [
            'client_id'    => $this->client->id,
            'warehouse_id' => $this->wh->id,
            'date'         => now()->toDateString(),
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 10000, 'discount_pct' => 0],
            ],
        ])->assertCreated();

        $this->assertEquals(50000, $resp->json('data.total'));
    }

    public function test_can_confirm_sale_order(): void
    {
        $order = SaleOrder::create([
            'company_id'     => $this->company->id,
            'client_id'      => $this->client->id,
            'warehouse_id'   => $this->wh->id,
            'user_id'        => $this->user->id,
            'number'         => 'PE-001',
            'date'           => now(),
            'status'         => 'draft',
            'subtotal'       => 50000,
            'discount_total' => 0,
            'tax'            => 0,
            'total'          => 50000,
        ]);

        $this->postJson("/api/sale-orders/{$order->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    // ── 3. CRUD Facturas de venta ────────────────────────────────────────────

    public function test_can_create_sale_invoice(): void
    {
        $order = $this->makeSaleOrder();

        $resp = $this->postJson('/api/sale-invoices', [
            'client_id'      => $this->client->id,
            'sale_order_id'  => $order->id,
            'number'         => 'FV-001',
            'date'           => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'items'          => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10000, 'discount_pct' => 0],
            ],
        ])->assertCreated();

        $this->assertEquals(20000, $resp->json('data.total'));
    }

    // ── 4. Postear factura genera CxC ────────────────────────────────────────

    public function test_post_invoice_creates_accounts_receivable(): void
    {
        $invoice = $this->makeSaleInvoice('FV-002', 20000);

        $this->postJson("/api/sale-invoices/{$invoice->id}/post")->assertOk();

        $cxc = AccountsReceivable::where('sale_invoice_id', $invoice->id)->first();
        $this->assertNotNull($cxc);
        $this->assertEquals(20000, $cxc->amount);
        $this->assertEquals('open', $cxc->status);
    }

    // ── 5. Postear factura reduce stock ─────────────────────────────────────

    public function test_post_invoice_decreases_stock(): void
    {
        $invoice = $this->makeSaleInvoice('FV-003', 30000, 3);

        $stockBefore = (float) ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->wh->id)->value('quantity');

        $this->postJson("/api/sale-invoices/{$invoice->id}/post")->assertOk();

        $stockAfter = (float) ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->wh->id)->value('quantity');

        $this->assertEquals($stockBefore - 3, $stockAfter);
    }

    // ── 6. Postear captura cost_at_time ─────────────────────────────────────

    public function test_post_invoice_captures_cost_at_time(): void
    {
        $invoice = $this->makeSaleInvoice('FV-004', 10000, 1);

        $this->postJson("/api/sale-invoices/{$invoice->id}/post")->assertOk();

        $item = SaleInvoiceItem::where('sale_invoice_id', $invoice->id)->first();
        // avg_cost inicial del setUp era 5000
        $this->assertEquals(5000, $item->cost_at_time);
    }

    // ── 7. Doble posteo falla ────────────────────────────────────────────────

    public function test_double_post_invoice_returns_422(): void
    {
        $invoice = $this->makeSaleInvoice('FV-005', 10000);
        $invoice->update(['status' => 'posted']);

        $this->postJson("/api/sale-invoices/{$invoice->id}/post")->assertStatus(422);
    }

    // ── 8. Crear nota crédito (return) ──────────────────────────────────────

    public function test_can_create_return_from_posted_invoice(): void
    {
        $invoice = $this->makeSaleInvoice('FV-006', 10000, 1);
        $this->postJson("/api/sale-invoices/{$invoice->id}/post")->assertOk();

        $stockAfterSale = (float) ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->wh->id)->value('quantity');

        $this->postJson("/api/sale-invoices/{$invoice->id}/return")->assertCreated();

        // Stock debería haber vuelto
        $stockAfterReturn = (float) ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->wh->id)->value('quantity');

        $this->assertEquals($stockAfterSale + 1, $stockAfterReturn);
    }

    // ── 9. Multitenancy: no ve CxC de otra empresa ──────────────────────────

    public function test_cannot_see_other_company_accounts_receivable(): void
    {
        $other    = Company::factory()->create(['name' => 'Otra Empresa Ventas']);
        $otherCli = Client::create(['company_id' => $other->id, 'first_name' => 'Cliente', 'last_name' => 'Otro', 'name' => 'Cliente Otro', 'status' => 'active']);
        $otherUsr = User::factory()->create(['company_id' => $other->id]);
        $otherInv = SaleInvoice::create([
            'company_id'     => $other->id,
            'client_id'      => $otherCli->id,
            'user_id'        => $otherUsr->id,
            'number'         => 'FV-OTHER',
            'date'           => now(),
            'due_date'       => now()->addDays(30),
            'type'           => 'invoice',
            'status'         => 'draft',
            'subtotal'       => 1000,
            'discount_total' => 0,
            'tax'            => 0,
            'total'          => 1000,
        ]);
        AccountsReceivable::create([
            'company_id'      => $other->id,
            'client_id'       => $otherCli->id,
            'sale_invoice_id' => $otherInv->id,
            'amount'          => 1000,
            'balance'         => 1000,
            'due_date'        => now()->addDays(30),
            'status'          => 'open',
        ]);

        $resp = $this->getJson('/api/accounts-receivable');
        $ids  = collect($resp->json('data'))->pluck('id');
        $this->assertEmpty($ids);
    }

    // ── 10. CxC list requiere solo sales.view ────────────────────────────────

    public function test_can_list_accounts_receivable(): void
    {
        $this->getJson('/api/accounts-receivable')->assertOk();
    }

    // ── 11. Descuento se aplica al total ────────────────────────────────────

    public function test_discount_applied_to_quote_total(): void
    {
        $resp = $this->postJson('/api/quotes', [
            'client_id'   => $this->client->id,
            'date'        => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'items'       => [
                ['product_id' => $this->product->id, 'quantity' => 4, 'unit_price' => 10000, 'discount_pct' => 10],
            ],
        ])->assertCreated();

        // 4 * 10000 = 40000, 10% disc = 4000, total = 36000
        $this->assertEquals(36000, $resp->json('data.total'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeSaleOrder(): SaleOrder
    {
        $order = SaleOrder::create([
            'company_id'     => $this->company->id,
            'client_id'      => $this->client->id,
            'warehouse_id'   => $this->wh->id,
            'user_id'        => $this->user->id,
            'number'         => 'PE-'.random_int(100, 999),
            'date'           => now(),
            'status'         => 'confirmed',
            'subtotal'       => 50000,
            'discount_total' => 0,
            'tax'            => 0,
            'total'          => 50000,
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id'    => $this->product->id,
            'quantity'      => 5,
            'unit_price'    => 10000,
            'discount_pct'  => 0,
            'subtotal'      => 50000,
        ]);

        return $order;
    }

    private function makeSaleInvoice(string $number, float $total, int $qty = 2): SaleInvoice
    {
        $order   = $this->makeSaleOrder();
        $invoice = SaleInvoice::create([
            'company_id'     => $this->company->id,
            'client_id'      => $this->client->id,
            'sale_order_id'  => $order->id,
            'user_id'        => $this->user->id,
            'number'         => $number,
            'date'           => now(),
            'due_date'       => now()->addDays(30),
            'type'           => 'invoice',
            'status'         => 'draft',
            'subtotal'       => $total,
            'discount_total' => 0,
            'tax'            => 0,
            'total'          => $total,
        ]);

        SaleInvoiceItem::create([
            'sale_invoice_id' => $invoice->id,
            'product_id'      => $this->product->id,
            'quantity'        => $qty,
            'unit_price'      => $total / $qty,
            'discount_pct'    => 0,
            'subtotal'        => $total,
            'cost_at_time'    => 0,
        ]);

        return $invoice;
    }
}
