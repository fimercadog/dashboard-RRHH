<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidProductTypeException;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $svc;
    private Company $company;
    private User $user;
    private Warehouse $wh;
    private Product $storable;
    private Product $service;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);

        $this->svc     = app(StockService::class);
        $this->company = Company::factory()->create(['name' => 'Test SA']);
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);
        $this->wh      = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Principal', 'status' => 'active']);
        $this->storable = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Resma papel', 'type' => 'storable',
            'cost_price' => 10000, 'sale_price' => 15000, 'status' => 'active',
        ]);
        $this->service = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Consultoría', 'type' => 'service',
            'cost_price' => 0, 'sale_price' => 100000, 'status' => 'active',
        ]);
        $this->actingAs($this->user);
    }

    public function test_entry_increases_stock_and_calculates_avg_cost(): void
    {
        $this->svc->entry($this->storable, $this->wh, 10, 10000, user_id: $this->user->id);

        $stock = ProductStock::where('product_id', $this->storable->id)->first();
        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals(10000, $stock->avg_cost);
    }

    public function test_second_entry_recalculates_weighted_avg_cost(): void
    {
        $this->svc->entry($this->storable, $this->wh, 10, 10000, user_id: $this->user->id);
        $this->svc->entry($this->storable, $this->wh, 5, 14000, user_id: $this->user->id);

        $stock = ProductStock::where('product_id', $this->storable->id)->first();
        $this->assertEquals(15, $stock->quantity);
        // (10*10000 + 5*14000) / 15 = 11333.33...
        $this->assertEqualsWithDelta(11333.33, $stock->avg_cost, 1);
    }

    public function test_exit_decreases_stock(): void
    {
        $this->svc->entry($this->storable, $this->wh, 10, 10000, user_id: $this->user->id);
        $this->svc->exit($this->storable, $this->wh, 3, user_id: $this->user->id);

        $stock = ProductStock::where('product_id', $this->storable->id)->first();
        $this->assertEquals(7, $stock->quantity);
    }

    public function test_exit_does_not_recalculate_avg_cost(): void
    {
        $this->svc->entry($this->storable, $this->wh, 10, 10000, user_id: $this->user->id);
        $avg_before = ProductStock::where('product_id', $this->storable->id)->value('avg_cost');

        $this->svc->exit($this->storable, $this->wh, 3, user_id: $this->user->id);

        $avg_after = ProductStock::where('product_id', $this->storable->id)->value('avg_cost');
        $this->assertEquals($avg_before, $avg_after);
    }

    public function test_exit_throws_when_insufficient_stock(): void
    {
        $this->svc->entry($this->storable, $this->wh, 5, 10000, user_id: $this->user->id);

        $this->expectException(InsufficientStockException::class);
        $this->svc->exit($this->storable, $this->wh, 10, user_id: $this->user->id);
    }

    public function test_service_product_throws_on_any_movement(): void
    {
        $this->expectException(InvalidProductTypeException::class);
        $this->svc->entry($this->service, $this->wh, 1, 0, user_id: $this->user->id);
    }

    public function test_adjust_sets_absolute_stock(): void
    {
        $this->svc->entry($this->storable, $this->wh, 10, 10000, user_id: $this->user->id);
        $this->svc->adjust($this->storable, $this->wh, 7, 'Conteo físico', user_id: $this->user->id);

        $this->assertEquals(7, ProductStock::where('product_id', $this->storable->id)->value('quantity'));
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $wh2 = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Sucursal', 'status' => 'active']);
        $this->svc->entry($this->storable, $this->wh, 20, 10000, user_id: $this->user->id);

        [$out, $in] = $this->svc->transfer($this->storable, $this->wh, $wh2, 8, user_id: $this->user->id);

        $this->assertEquals(12, ProductStock::where('product_id', $this->storable->id)->where('warehouse_id', $this->wh->id)->value('quantity'));
        $this->assertEquals(8, ProductStock::where('product_id', $this->storable->id)->where('warehouse_id', $wh2->id)->value('quantity'));
        // Ambos movimientos comparten el mismo transfer_id.
        $this->assertEquals($out->transfer_id, $in->transfer_id);
    }

    public function test_transfer_rollsback_on_insufficient_stock(): void
    {
        $wh2 = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Sucursal', 'status' => 'active']);
        $this->svc->entry($this->storable, $this->wh, 5, 10000, user_id: $this->user->id);

        $this->expectException(InsufficientStockException::class);
        $this->svc->transfer($this->storable, $this->wh, $wh2, 10, user_id: $this->user->id);

        // Stock original no debe haber cambiado.
        $this->assertEquals(5, ProductStock::where('product_id', $this->storable->id)->value('quantity'));
    }

    public function test_movements_and_stock_are_never_inconsistent(): void
    {
        // Simula N operaciones consecutivas y verifica que el stock
        // coincide con la suma de movimientos.
        $this->svc->entry($this->storable, $this->wh, 100, 5000, user_id: $this->user->id);
        $this->svc->exit($this->storable, $this->wh, 30, user_id: $this->user->id);
        $this->svc->entry($this->storable, $this->wh, 20, 6000, user_id: $this->user->id);
        $this->svc->exit($this->storable, $this->wh, 10, user_id: $this->user->id);

        $actualStock = ProductStock::where('product_id', $this->storable->id)->value('quantity');
        $this->assertEquals(80, $actualStock);
    }
}
