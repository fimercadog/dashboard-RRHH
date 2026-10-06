<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContingencyTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['name' => 'Test SA']);

        foreach (['settings.manage', 'attendance.manage', 'clients.manage', 'sales.manage', 'purchases.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function login(array $permissions = []): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->givePermissionTo($permissions);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_status_is_readable_by_any_authenticated_user(): void
    {
        $this->login([]);

        $this->getJson('/api/contingency/status')
            ->assertOk()
            ->assertJsonPath('active', false);
    }

    public function test_activate_requires_settings_manage(): void
    {
        $this->login([]);

        $this->postJson('/api/contingency/activate', ['enabled_modules' => ['attendances']])
            ->assertForbidden();
    }

    public function test_activate_validates_modules_against_registry(): void
    {
        $this->login(['settings.manage']);

        $this->postJson('/api/contingency/activate', ['enabled_modules' => ['payroll']])
            ->assertStatus(422);
    }

    public function test_full_session_lifecycle(): void
    {
        $this->login(['settings.manage']);

        $this->postJson('/api/contingency/activate', ['enabled_modules' => ['attendances']])
            ->assertCreated()
            ->assertJsonPath('active', true)
            ->assertJsonPath('session.enabled_modules', ['attendances']);

        // Segunda activacion mientras hay una activa -> 409.
        $this->postJson('/api/contingency/activate', ['enabled_modules' => ['attendances']])
            ->assertStatus(409);

        $this->postJson('/api/contingency/deactivate')
            ->assertOk()
            ->assertJsonPath('active', false);

        // Desactivar sin sesion activa -> 409.
        $this->postJson('/api/contingency/deactivate')->assertStatus(409);
    }

    public function test_attendance_create_is_idempotent_by_client_uuid(): void
    {
        $this->login(['attendance.manage']);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'employee_code' => 'EMP-0001',
            'first_name' => 'Ana',
            'last_name' => 'Diaz',
            'identification_type' => 'CC',
            'identification_number' => '1000000001',
            'hire_date' => '2026-01-01',
        ]);

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $payload = [
            'client_uuid' => $uuid,
            'employee_id' => $employee->id,
            'date' => '2026-09-01',
            'status' => 'present',
        ];

        $this->postJson('/api/attendances', $payload)->assertCreated();
        $this->postJson('/api/attendances', $payload)->assertOk();

        $this->assertSame(1, Attendance::where('client_uuid', $uuid)->count());
    }

    public function test_new_modules_appear_in_status_response(): void
    {
        $this->login([]);

        $this->getJson('/api/contingency/status')
            ->assertOk()
            ->assertJsonFragment(['key' => 'crm_clients'])
            ->assertJsonFragment(['key' => 'quotes'])
            ->assertJsonFragment(['key' => 'purchase_orders']);
    }

    public function test_crm_client_create_is_idempotent_by_client_uuid(): void
    {
        $this->login(['clients.manage']);

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $payload = [
            'client_uuid'           => $uuid,
            'first_name'            => 'Pedro',
            'last_name'             => 'Ramirez',
            'identification_type'   => 'CC',
            'identification_number' => '9000000001',
            'email'                 => 'pedro@example.com',
            'status'                => 'active',
        ];

        $this->postJson('/api/clients', $payload)->assertCreated();
        $this->postJson('/api/clients', $payload)->assertOk();

        $this->assertSame(1, Client::where('client_uuid', $uuid)->count());
    }

    public function test_quote_create_is_idempotent_by_client_uuid(): void
    {
        $this->login(['sales.manage']);

        $client = Client::create([
            'company_id' => $this->company->id,
            'first_name' => 'Ana',
            'last_name'  => 'Lopez',
            'status'     => 'active',
        ]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'name'       => 'Prod Cotizacion',
            'type'       => 'storable',
            'sale_price' => 10000,
            'status'     => 'active',
        ]);

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $payload = [
            'client_uuid' => $uuid,
            'client_id'   => $client->id,
            'date'        => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status'      => 'draft',
            'items'       => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000],
            ],
        ];

        $this->postJson('/api/quotes', $payload)->assertCreated();
        $this->postJson('/api/quotes', $payload)->assertOk();

        $this->assertSame(1, Quote::where('client_uuid', $uuid)->count());
    }

    public function test_purchase_order_create_is_idempotent_by_client_uuid(): void
    {
        $this->login(['purchases.manage']);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name'       => 'Proveedor Test',
            'status'     => 'active',
        ]);
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'name'       => 'Bodega Test',
            'status'     => 'active',
        ]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'name'       => 'Prod OC',
            'type'       => 'storable',
            'cost_price' => 5000,
            'status'     => 'active',
        ]);

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $payload = [
            'client_uuid'  => $uuid,
            'supplier_id'  => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'date'         => now()->toDateString(),
            'status'       => 'draft',
            'items'        => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 5000],
            ],
        ];

        $this->postJson('/api/purchase-orders', $payload)->assertCreated();
        $this->postJson('/api/purchase-orders', $payload)->assertOk();

        $this->assertSame(1, PurchaseOrder::where('client_uuid', $uuid)->count());
    }
}
