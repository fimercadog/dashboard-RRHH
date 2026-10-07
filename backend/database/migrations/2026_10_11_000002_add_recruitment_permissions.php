<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = ['recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.delete'];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        $rrhh = Role::where('name', 'Recursos Humanos')->where('guard_name', 'web')->first();
        if ($rrhh) {
            $rrhh->givePermissionTo(['recruitment.view', 'recruitment.create', 'recruitment.update']);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.delete'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
