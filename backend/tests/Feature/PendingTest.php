<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PendingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Company $otherCompany;
    private User $admin;
    private User $adminOther;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'requests.approve', 'documents.manage', 'leads.view', 'activities.manage',
            'deals.manage', 'purchases.manage', 'purchases.view', 'sales.manage',
            'sales.view', 'finance.manage', 'finance.view', 'dashboard.view',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole   = Role::firstOrCreate(['name' => 'Administrador de empresa', 'guard_name' => 'web']);
        $adminRole->syncPermissions($perms);
        $empleadoRole = Role::firstOrCreate(['name' => 'Empleado', 'guard_name' => 'web']);
        $empleadoRole->syncPermissions(['dashboard.view']);

        $this->company      = Company::factory()->create(['name' => 'Test Pending SA']);
        $this->otherCompany = Company::factory()->create(['name' => 'Otra Empresa Pending']);

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole('Administrador de empresa');

        $this->adminOther = User::factory()->create(['company_id' => $this->otherCompany->id]);
        $this->adminOther->assignRole('Administrador de empresa');
    }

    public function test_pending_requires_auth(): void
    {
        $this->getJson('/api/pending')->assertStatus(401);
    }

    public function test_returns_empty_when_no_pending_items(): void
    {
        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/pending');

        $res->assertOk()
            ->assertJsonStructure(['items', 'total'])
            ->assertJsonPath('total', 0)
            ->assertJsonPath('items', []);
    }

    public function test_counts_vacation_requests_for_own_company(): void
    {
        $emp = Employee::factory()->create(['company_id' => $this->company->id]);

        VacationRequest::factory()->count(3)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/pending');

        $res->assertOk();

        $total = $res->json('total');
        $this->assertGreaterThanOrEqual(3, $total, 'total should include 3 vacation requests');

        $types = collect($res->json('items'))->pluck('type')->all();
        $this->assertContains('vacation_request', $types);
    }

    public function test_badge_total_sums_all_counts(): void
    {
        $emp = Employee::factory()->create(['company_id' => $this->company->id]);

        VacationRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        PermissionRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/pending');
        $res->assertOk();

        $itemTotal = collect($res->json('items'))->sum('count');
        $this->assertEquals($res->json('total'), $itemTotal, 'total must equal sum of item counts');
        $this->assertGreaterThanOrEqual(4, $res->json('total'));
    }

    public function test_company_isolation_empresa_a_cannot_see_empresa_b_pending(): void
    {
        $emp = Employee::factory()->create(['company_id' => $this->otherCompany->id]);

        VacationRequest::factory()->count(5)->create([
            'company_id'  => $this->otherCompany->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        // Admin from company A should see 0 pending items.
        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/pending');
        $res->assertOk()->assertJsonPath('total', 0);
    }

    public function test_user_without_permissions_sees_no_hr_pending(): void
    {
        $emp = Employee::factory()->create(['company_id' => $this->company->id]);

        VacationRequest::factory()->count(3)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        $limited = User::factory()->create(['company_id' => $this->company->id]);
        $limited->assignRole('Empleado');

        $res = $this->actingAs($limited, 'sanctum')->getJson('/api/pending');
        $res->assertOk();

        $types = collect($res->json('items'))->pluck('type')->all();
        $this->assertNotContains('vacation_request', $types, 'Empleado role should not see HR pending items');
    }

    public function test_each_item_has_required_fields(): void
    {
        $emp = Employee::factory()->create(['company_id' => $this->company->id]);
        VacationRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/pending');
        $res->assertOk();

        foreach ($res->json('items') as $item) {
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('module', $item);
            $this->assertArrayHasKey('route', $item);
            $this->assertArrayHasKey('priority', $item);
            $this->assertArrayHasKey('count', $item);
        }
    }

    public function test_items_with_zero_count_are_excluded(): void
    {
        // No pending items created.
        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/pending');
        $res->assertOk();

        foreach ($res->json('items') as $item) {
            $this->assertGreaterThan(0, $item['count'], 'items with count=0 must not appear in response');
        }
    }

    public function test_high_priority_items_appear_first(): void
    {
        $emp = Employee::factory()->create(['company_id' => $this->company->id]);

        VacationRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $emp->id,
            'status'      => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/pending');
        $items = $res->json('items');

        $this->assertGreaterThanOrEqual(1, count($items), 'Should have at least 1 pending item');

        $order = ['high' => 0, 'medium' => 1, 'low' => 2];
        for ($i = 1; $i < count($items); $i++) {
            $this->assertLessThanOrEqual(
                $order[$items[$i]['priority']],
                $order[$items[$i - 1]['priority']],
                'Items should be sorted high priority first',
            );
        }
    }
}
