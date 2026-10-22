<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function store_ignores_company_id_in_payload(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $actor = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');

        $response = $this->actingAs($actor, 'sanctum')->postJson('/api/users', [
            'name'       => 'Injected User',
            'email'      => 'injected@example.com',
            'company_id' => $companyB->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email'      => 'injected@example.com',
            'company_id' => $companyA->id,
        ]);
        $this->assertDatabaseMissing('users', [
            'email'      => 'injected@example.com',
            'company_id' => $companyB->id,
        ]);
    }

    #[Test]
    public function cannot_assign_role_from_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $actor = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');

        // Role that belongs to company B — company A users cannot assign it.
        Role::create(['name' => 'ManagerB', 'guard_name' => 'web', 'company_id' => $companyB->id]);

        $target = User::factory()->create(['company_id' => $companyA->id]);

        $this->actingAs($actor, 'sanctum')
            ->putJson("/api/users/{$target->id}", [
                'name' => 'Updated',
                'role' => 'ManagerB',
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function update_ignores_company_id_in_payload(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $actor  = User::factory()->create(['company_id' => $companyA->id]);
        $target = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');

        $this->actingAs($actor, 'sanctum')->putJson("/api/users/{$target->id}", [
            'name'       => 'Updated',
            'company_id' => $companyB->id,
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id'         => $target->id,
            'company_id' => $companyA->id,
        ]);
    }

    // ─── H-UC1: IDOR employee_id (CENTINELA hallazgo) ────────────────────────

    #[Test]
    public function store_rejects_employee_id_from_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $actor    = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');
        $empB     = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($actor, 'sanctum')->postJson('/api/users', [
            'name'        => 'Hacker',
            'email'       => 'hacker@example.com',
            'employee_id' => $empB->id,
        ])->assertStatus(404);

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    #[Test]
    public function update_rejects_employee_id_from_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $actor  = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');
        $target = User::factory()->create(['company_id' => $companyA->id]);
        $empB   = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($actor, 'sanctum')->putJson("/api/users/{$target->id}", [
            'employee_id' => $empB->id,
        ])->assertStatus(404);

        // employee_id del target debe seguir siendo null (sin cambio)
        $this->assertDatabaseHas('users', ['id' => $target->id, 'employee_id' => null]);
    }

    #[Test]
    public function store_rejects_avatar_path_in_payload(): void
    {
        $companyA = Company::factory()->create();
        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $actor = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');

        $response = $this->actingAs($actor, 'sanctum')->postJson('/api/users', [
            'name'        => 'Test',
            'email'       => 'test-avpath@example.com',
            'avatar_path' => 'avatars/other-company/secret.jpg',
        ])->assertStatus(201);

        // avatar_path enviado como texto debe ser ignorado
        $this->assertDatabaseHas('users', ['email' => 'test-avpath@example.com', 'avatar_path' => null]);
    }
}
