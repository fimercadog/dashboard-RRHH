<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'accounting.view',    // ver plan, períodos, asientos, reportes
            'accounting.manage',  // crear/editar cuentas, asientos manuales
            'accounting.post',    // contabilizar asientos draft
            'accounting.close',   // cerrar períodos (irreversible)
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['accounting.view', 'accounting.manage', 'accounting.post', 'accounting.close'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
