<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $vetPermissions = [
        'appointments.manage',
        'patients.manage',
        'medical_records.manage',
        'vaccinations.manage',
        'prescriptions.manage',
        'procedures.manage',
        'clinical_reports.view',
        'services.manage',
    ];

    public function up(): void
    {
        // Orden FK: appointments primero, luego patients, luego breeds/species/services.
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('breeds');
        Schema::dropIfExists('species');
        Schema::dropIfExists('services');

        // Roles clínicos — sin usuarios reales en este ERP.
        Role::whereIn('name', ['Veterinario', 'Recepcionista'])
            ->where('guard_name', 'web')
            ->each(fn ($role) => $role->delete());

        // Permisos exclusivamente clínicos.
        Permission::whereIn('name', $this->vetPermissions)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No recrea las tablas clínicas — usar git restore + migrate:fresh si se necesita volver.
    }
};
