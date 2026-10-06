<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Company::factory()->create(['name' => 'Test SA']);
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    }

    public function test_requires_dashboard_view_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_returns_the_expected_shape(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'metrics' => ['total_employees', 'active_employees', 'present_today'],
                'deltas' => ['hires' => ['current', 'previous', 'pct'], 'requests', 'attendance_rate'],
                'attendance_funnel',
                'headcount_by_department',
                'headcount_by_status',
                'trends' => ['attendance_monthly', 'headcount_flow', 'requests_monthly'],
                'recent_activity',
                'upcoming_events' => ['documents', 'birthdays'],
            ])
            ->assertJsonCount(12, 'trends.attendance_monthly');
    }
}
