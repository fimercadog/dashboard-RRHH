<?php

namespace Tests\Feature\Demo;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PermissionRequest;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\VacationRequest;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private Company $demoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->demoCompany = Company::where('name', 'Empresa Demo SAS')->firstOrFail();
    }

    // 1. Empresa existe después del seeder
    public function test_demo_company_created(): void
    {
        $this->assertDatabaseHas('companies', [
            'name' => 'Empresa Demo SAS',
            'nit'  => '900.999.888-1',
        ]);
    }

    // 2. Usuarios demo existen con roles correctos
    public function test_demo_users_have_correct_roles(): void
    {
        $cases = [
            ['admin.demo@empresademo.co',     'Administrador de empresa'],
            ['rrhh.demo@empresademo.co',       'Recursos Humanos'],
            ['supervisor.demo@empresademo.co', 'Supervisor'],
            ['empleado.demo@empresademo.co',   'Empleado'],
        ];

        foreach ($cases as [$email, $role]) {
            $user = \App\Models\User::where('email', $email)->firstOrFail();
            $this->assertTrue(
                $user->hasRole($role),
                "Usuario {$email} deberia tener rol {$role}"
            );
        }
    }

    // 3. Datos RRHH poblados
    public function test_hr_data_seeded_for_demo_company(): void
    {
        $this->assertGreaterThan(
            15,
            Employee::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 15 empleados demo'
        );
        $this->assertGreaterThan(
            100,
            Attendance::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 100 asistencias demo'
        );
        $this->assertGreaterThan(
            3,
            VacationRequest::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 3 vacaciones demo'
        );
        $this->assertGreaterThan(
            3,
            PermissionRequest::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 3 permisos demo'
        );
    }

    // 4. Datos CRM poblados
    public function test_crm_data_seeded_for_demo_company(): void
    {
        $this->assertGreaterThan(
            3,
            Client::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 3 clientes demo'
        );
        $this->assertGreaterThan(
            5,
            \App\Models\Deal::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 5 deals demo'
        );
        $this->assertGreaterThan(
            3,
            \App\Models\Activity::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 3 actividades demo'
        );
    }

    // 5. Datos inventario poblados
    public function test_inventory_data_seeded_for_demo_company(): void
    {
        $this->assertGreaterThan(
            5,
            Product::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 5 productos demo'
        );
        $this->assertGreaterThanOrEqual(
            2,
            Warehouse::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber al menos 2 bodegas demo'
        );
        $this->assertGreaterThan(
            5,
            \App\Models\ProductStock::where('company_id', $this->demoCompany->id)->count(),
            'Debe haber mas de 5 registros de stock demo'
        );
    }

    // 6. Relaciones CRM -> Ventas verificadas
    public function test_sales_linked_to_crm_clients(): void
    {
        $salesOrders = SaleOrder::where('company_id', $this->demoCompany->id)->get();
        $this->assertNotEmpty($salesOrders, 'Debe haber ordenes de venta demo');

        $clientIds = Client::where('company_id', $this->demoCompany->id)->pluck('id');
        foreach ($salesOrders as $order) {
            $this->assertContains(
                $order->client_id,
                $clientIds->toArray(),
                "OV {$order->number} tiene client_id que no pertenece a la empresa demo"
            );
        }
    }

    // 7. Idempotencia — segunda ejecucion no duplica
    public function test_idempotency_no_duplicates_on_rerun(): void
    {
        $before = [
            'employees'  => Employee::where('company_id', $this->demoCompany->id)->count(),
            'clients'    => Client::where('company_id', $this->demoCompany->id)->count(),
            'products'   => Product::where('company_id', $this->demoCompany->id)->count(),
            'warehouses' => Warehouse::where('company_id', $this->demoCompany->id)->count(),
        ];

        $this->seed(DemoSeeder::class);

        $after = [
            'employees'  => Employee::where('company_id', $this->demoCompany->id)->count(),
            'clients'    => Client::where('company_id', $this->demoCompany->id)->count(),
            'products'   => Product::where('company_id', $this->demoCompany->id)->count(),
            'warehouses' => Warehouse::where('company_id', $this->demoCompany->id)->count(),
        ];

        foreach ($before as $key => $count) {
            $this->assertEquals($count, $after[$key], "Segunda ejecucion duplico {$key}");
        }
    }

    // 8. Aislamiento — usuario demo no ve datos de Andes People Solutions
    public function test_demo_user_cannot_access_andes_data(): void
    {
        $demoAdmin = \App\Models\User::where('email', 'admin.demo@empresademo.co')->firstOrFail();
        Sanctum::actingAs($demoAdmin, ['*']);

        $response = $this->getJson('/api/employees');
        $response->assertOk();

        $data = $response->json('data') ?? [];
        foreach ($data as $employee) {
            $this->assertEquals(
                $this->demoCompany->id,
                $employee['company_id'],
                'Un empleado en la respuesta no pertenece a la empresa demo'
            );
        }
    }

    // 9. Aislamiento inverso — usuario Andes no ve datos demo
    public function test_andes_user_cannot_access_demo_data(): void
    {
        $andesAdmin = \App\Models\User::where('email', 'admin@andespeople.co')->firstOrFail();
        Sanctum::actingAs($andesAdmin, ['*']);

        $response = $this->getJson('/api/clients');
        $response->assertOk();

        $data = $response->json('data') ?? [];
        foreach ($data as $client) {
            $this->assertNotEquals(
                $this->demoCompany->id,
                $client['company_id'] ?? null,
                'Un cliente de la empresa demo aparecio en la respuesta de Andes'
            );
        }
    }

    // 10. Autenticacion de todos los roles demo
    public function test_all_demo_users_can_authenticate(): void
    {
        $password = config('app.demo_password', 'DFC-Demo-2026!');
        $emails   = [
            'admin.demo@empresademo.co',
            'rrhh.demo@empresademo.co',
            'supervisor.demo@empresademo.co',
            'empleado.demo@empresademo.co',
        ];

        foreach ($emails as $email) {
            $response = $this->postJson('/api/auth/login', [
                'email'    => $email,
                'password' => $password,
            ]);
            $response->assertOk(
                "Login fallido para {$email}: ".$response->getContent()
            );
            $this->assertNotEmpty(
                $response->json('token'),
                "Sin token para {$email}"
            );
        }
    }

    // 11. Dashboard responde con datos para empresa demo
    public function test_demo_company_dashboard_returns_data(): void
    {
        $demoAdmin = \App\Models\User::where('email', 'admin.demo@empresademo.co')->firstOrFail();
        Sanctum::actingAs($demoAdmin, ['*']);

        $response = $this->getJson('/api/dashboard');
        $response->assertOk();

        $this->assertGreaterThan(
            0,
            $response->json('metrics.total_employees') ?? $response->json('total_employees') ?? $response->json('data.total_employees') ?? 0,
            'dashboard metrics.total_employees debe ser > 0 para empresa demo'
        );
    }

    // 12. Asistencias acotadas a empresa demo
    public function test_attendance_scoped_to_demo_company(): void
    {
        $count = Attendance::where('company_id', $this->demoCompany->id)->count();
        $this->assertGreaterThan(100, $count, 'Debe haber mas de 100 asistencias para empresa demo');

        // Verificar que todos los employee_id en asistencias demo pertenecen a empleados de la empresa demo
        $demoEmployeeIds = Employee::where('company_id', $this->demoCompany->id)->pluck('id');
        $leaked = Attendance::where('company_id', $this->demoCompany->id)
            ->whereNotIn('employee_id', $demoEmployeeIds)
            ->count();
        $this->assertEquals(0, $leaked, 'Hay asistencias demo con employee_id de otra empresa');
    }
}
