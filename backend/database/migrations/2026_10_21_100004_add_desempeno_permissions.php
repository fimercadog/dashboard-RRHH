<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['desempeno.view', 'desempeno.manage', 'desempeno.delete'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'Administrador de empresa')->first();
        if ($admin) {
            $admin->givePermissionTo(['desempeno.view', 'desempeno.manage']);
        }

        $rrhh = Role::where('name', 'Recursos Humanos')->first();
        if ($rrhh) {
            $rrhh->givePermissionTo(['desempeno.view', 'desempeno.manage']);
        }
    }

    public function down(): void
    {
        foreach (['desempeno.view', 'desempeno.manage', 'desempeno.delete'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
