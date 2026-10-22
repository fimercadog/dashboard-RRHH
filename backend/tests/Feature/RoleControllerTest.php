<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(Company $company): User
    {
        Permission::firstOrCreate(['name' => 'roles.manage', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('roles.manage');
        return $user;
    }

    #[Test]
    public function index_returns_only_own_company_roles(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        // Bypass Spatie's static create() which doesn't know about company_id:
        // insert directly so company_id is guaranteed.
        \DB::table('roles')->insert([
            ['name' => 'RoleA', 'guard_name' => 'web', 'company_id' => $companyA->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'RoleB', 'guard_name' => 'web', 'company_id' => $companyB->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $actor = $this->makeAdmin($companyA);
        $response = $this->actingAs($actor, 'sanctum')->getJson('/api/roles');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('RoleA', $names);
        $this->assertNotContains('RoleB', $names);
    }

    #[Test]
    public function store_assigns_company_id_from_token(): void
    {
        $companyA = Company::factory()->create();
        $actor    = $this->makeAdmin($companyA);

        $response = $this->actingAs($actor, 'sanctum')->postJson('/api/roles', [
            'name' => 'NuevoRol',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', [
            'name'       => 'NuevoRol',
            'company_id' => $companyA->id,
        ]);
    }

    #[Test]
    public function update_cannot_modify_other_company_role(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $roleB    = Role::create(['name' => 'RoleB', 'guard_name' => 'web', 'company_id' => $companyB->id]);
        $actor    = $this->makeAdmin($companyA);

        $this->actingAs($actor, 'sanctum')
            ->putJson("/api/roles/{$roleB->id}", ['name' => 'Hacked'])
            ->assertNotFound();

        $this->assertDatabaseHas('roles', ['id' => $roleB->id, 'name' => 'RoleB']);
    }

    #[Test]
    public function user_cannot_assign_role_from_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);
        Role::create(['name' => 'RoleB', 'guard_name' => 'web', 'company_id' => $companyB->id]);

        $actor  = User::factory()->create(['company_id' => $companyA->id]);
        $actor->givePermissionTo('users.manage');
        $target = User::factory()->create(['company_id' => $companyA->id]);

        $this->actingAs($actor, 'sanctum')
            ->putJson("/api/users/{$target->id}", ['role' => 'RoleB'])
            ->assertNotFound();
    }
}
