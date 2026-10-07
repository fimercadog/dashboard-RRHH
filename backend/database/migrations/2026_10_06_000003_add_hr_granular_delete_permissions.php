<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Granulariza los DELETE de los módulos RRHH.
     *
     * Motivación: incidente 2026-10-06 — RRHH podía eliminar empleados via
     * employees.manage. La misma vulnerabilidad existe en attendance.manage,
     * requests.approve y documents.manage: todos exponían destroy implícitamente.
     *
     * Principio: manage ≠ delete, approve ≠ delete.
     *
     * - attendance.delete  → separa destroy de attendances/shifts
     * - requests.delete    → separa destroy de vacation/permission/sick-leave requests
     * - documents.delete   → separa destroy de employee-documents
     *
     * RRHH conserva manage/approve pero NO recibe los nuevos delete.
     * Solo Super Admin y Administrador de empresa los reciben.
     */
    public function up(): void
    {
        foreach (['attendance.delete', 'requests.delete', 'documents.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Administrador de empresa'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo(['attendance.delete', 'requests.delete', 'documents.delete']);
        }

        // RRHH: conserva attendance.manage, requests.approve, documents.manage
        // pero NO recibe ningún .delete — puede gestionar/aprobar, no eliminar.

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['attendance.delete', 'requests.delete', 'documents.delete'] as $perm) {
            Permission::where('name', $perm)->where('guard_name', 'web')->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
