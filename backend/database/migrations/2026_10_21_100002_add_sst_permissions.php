<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['sst.view', 'sst.manage', 'sst.delete'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'Administrador de empresa')->first();
        if ($admin) {
            $admin->givePermissionTo(['sst.view', 'sst.manage']);
        }

        $rrhh = Role::where('name', 'Recursos Humanos')->first();
        if ($rrhh) {
            $rrhh->givePermissionTo(['sst.view', 'sst.manage']);
        }
    }

    public function down(): void
    {
        foreach (['sst.view', 'sst.manage', 'sst.delete'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
