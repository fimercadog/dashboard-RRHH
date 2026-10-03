<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $newPermissions = [
        'appointments.manage',
        'patients.manage',
        'medical_records.manage',
        'vaccinations.manage',
        'prescriptions.manage',
        'procedures.manage',
        'clinical_reports.view',
        'services.manage',
        'clients.manage',
        'products.manage',
        'warehouses.manage',
        'stock.manage',
        'suppliers.manage',
        'deals.manage',
        'orders.manage',
        'invoices.manage',
        'payments.manage',
        'accounts_receivable.view',
        'accounts_payable.view',
        'cash.manage',
        'purchase_orders.manage',
        'purchase_receipts.manage',
        'activities.manage',
    ];

    public function up(): void
    {
        // dashboard.view vive en el seeder; en tests (RefreshDatabase) no existe aun.
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);

        foreach ($this->newPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Super Admin y Administrador de empresa reciben todos los permisos nuevos.
        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($this->newPermissions);
            }
        }

        // Rol Veterinario: acceso clínico completo.
        $vet = Role::firstOrCreate(['name' => 'Veterinario', 'guard_name' => 'web']);
        $vet->syncPermissions([
            'dashboard.view',
            'appointments.manage',
            'patients.manage',
            'medical_records.manage',
            'vaccinations.manage',
            'prescriptions.manage',
            'procedures.manage',
            'clinical_reports.view',
        ]);

        // Rol Recepcionista: agenda y clientes.
        $rec = Role::firstOrCreate(['name' => 'Recepcionista', 'guard_name' => 'web']);
        $rec->syncPermissions([
            'dashboard.view',
            'appointments.manage',
            'patients.manage',
            'clients.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['Veterinario', 'Recepcionista'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->delete();
        }

        Permission::whereIn('name', $this->newPermissions)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
