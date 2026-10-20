<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'payroll.view',
            'payroll.manage',
            'payroll.close',
            'payroll.reverse',
            'payroll.params.manage',
            'payroll.delete',
            'payroll.validate_tax_profile',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Super Admin gets all payroll permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Administrador de empresa gets view + manage + close + validate_tax_profile
        $admin = Role::where('name', 'Administrador de empresa')->first();
        if ($admin) {
            $admin->givePermissionTo(['payroll.view', 'payroll.manage', 'payroll.close', 'payroll.params.manage', 'payroll.validate_tax_profile']);
        }

        // Recursos Humanos gets view + manage
        $rrhh = Role::where('name', 'Recursos Humanos')->first();
        if ($rrhh) {
            $rrhh->givePermissionTo(['payroll.view', 'payroll.manage']);
        }
        // payroll.delete y payroll.reverse quedan solo para Super Admin
    }

    public function down(): void
    {
        $permissions = [
            'payroll.view',
            'payroll.manage',
            'payroll.close',
            'payroll.reverse',
            'payroll.params.manage',
            'payroll.delete',
            'payroll.validate_tax_profile',
        ];

        foreach ($permissions as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
