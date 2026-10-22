<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure employees.manage permission exists (guard web)
        $permission = Permission::firstOrCreate([
            'name' => 'employees.manage',
            'guard_name' => 'web',
        ]);

        // Assign to all admin-equivalent roles across all companies
        $adminRoleNames = ['Super Admin', 'Administrador de empresa'];

        Role::whereIn('name', $adminRoleNames)->each(function (Role $role) use ($permission) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        });

        // Clear spatie permission cache so changes take effect immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Not reversing: this is a data-correctness fix, removing it would break the app
    }
};
