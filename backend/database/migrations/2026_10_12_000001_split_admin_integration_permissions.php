<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear integrations.view si no existe.
        $integView = Permission::firstOrCreate(['name' => 'integrations.view', 'guard_name' => 'web']);

        // 2-3. Asignar integrations.view a ambos roles admin.
        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo('integrations.view')) {
                $role->givePermissionTo($integView);
            }
        }

        // 4-5. Revocar whatsapp.manage y ai.manage del Administrador de empresa.
        $admin = Role::where('name', 'Administrador de empresa')->where('guard_name', 'web')->first();
        if ($admin) {
            foreach (['whatsapp.manage', 'ai.manage'] as $permName) {
                if ($admin->hasPermissionTo($permName)) {
                    $admin->revokePermissionTo($permName);
                }
            }
        }
    }

    public function down(): void
    {
        // Revocar integrations.view de ambos roles admin y restaurar permisos al Administrador.
        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $role->hasPermissionTo('integrations.view')) {
                $role->revokePermissionTo('integrations.view');
            }
        }

        $admin = Role::where('name', 'Administrador de empresa')->where('guard_name', 'web')->first();
        if ($admin) {
            foreach (['whatsapp.manage', 'ai.manage'] as $permName) {
                $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
                if ($perm && ! $admin->hasPermissionTo($permName)) {
                    $admin->givePermissionTo($perm);
                }
            }
        }
    }
};
