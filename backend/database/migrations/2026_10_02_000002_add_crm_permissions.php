<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // deals.manage y activities.manage ya existen desde la migración Fase 1.
    private array $newPermissions = [
        'contacts.manage',
        'segments.manage',
        'clients.delete',
    ];

    public function up(): void
    {
        // Garantizar que los permisos de Fase 1 existan (tests con RefreshDatabase).
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'deals.manage',      'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'activities.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'leads.view',        'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'clients.manage',    'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reports.view',      'guard_name' => 'web']);

        foreach ($this->newPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($this->newPermissions);
        }

        $ventas = Role::firstOrCreate(['name' => 'Ventas', 'guard_name' => 'web']);
        $ventas->syncPermissions([
            'dashboard.view',
            'leads.view',
            'clients.manage',
            'contacts.manage',
            'deals.manage',
            'activities.manage',
            'segments.manage',
            'reports.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'Ventas')->where('guard_name', 'web')->first()?->delete();

        Permission::whereIn('name', $this->newPermissions)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
