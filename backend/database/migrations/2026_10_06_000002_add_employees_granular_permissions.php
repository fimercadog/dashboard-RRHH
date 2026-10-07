<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Permiso granular para el verbo DELETE de empleados.
        // employees.manage cubre index/show/store/update (rutas except destroy).
        // employees.delete solo se otorga a roles que pueden eliminar datos de personal.
        Permission::firstOrCreate(['name' => 'employees.delete', 'guard_name' => 'web']);

        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo('employees.delete');
        }

        // RRHH: NO recibe employees.delete — puede gestionar pero no eliminar.

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'employees.delete')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
