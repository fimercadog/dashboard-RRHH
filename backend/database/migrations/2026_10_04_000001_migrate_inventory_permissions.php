<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $obsolete = ['products.manage', 'warehouses.manage', 'stock.manage'];

    private array $new = ['inventory.manage', 'inventory.movements', 'inventory.view'];

    public function up(): void
    {
        // Eliminar permisos prematuros creados en Fase 1 que duplican inventory.*.
        Permission::whereIn('name', $this->obsolete)
            ->where('guard_name', 'web')
            ->each(fn ($p) => $p->delete());

        foreach ($this->new as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Admins reciben acceso completo.
        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($this->new);
        }

        // Rol operativo de almacén.
        $almacenista = Role::firstOrCreate(['name' => 'Almacenista', 'guard_name' => 'web']);
        $almacenista->syncPermissions([
            'dashboard.view',
            'inventory.manage',
            'inventory.movements',
            'inventory.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'Almacenista')->where('guard_name', 'web')->first()?->delete();

        Permission::whereIn('name', $this->new)
            ->where('guard_name', 'web')
            ->each(fn ($p) => $p->delete());

        foreach ($this->obsolete as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
