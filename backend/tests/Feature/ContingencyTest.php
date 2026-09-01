<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
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

        foreach (['settings.manage', 'attendance.manage'] as $name) {
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
}
