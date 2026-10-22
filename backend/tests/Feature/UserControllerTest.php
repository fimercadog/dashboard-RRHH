<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
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
}
