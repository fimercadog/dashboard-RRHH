<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['finance.manage', 'finance.view'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }

    public function down(): void
    {
        foreach (['finance.manage', 'finance.view'] as $name) {
            Permission::where(['name' => $name, 'guard_name' => 'web'])->delete();
        }
    }
};
