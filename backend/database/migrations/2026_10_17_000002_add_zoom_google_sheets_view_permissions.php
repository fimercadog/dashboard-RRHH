<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['zoom.view', 'google_sheets.view'];

        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roles = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Administrador de empresa'])
            ->where('guard_name', 'web')
            ->pluck('id');

        $permIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($roles as $roleId) {
            foreach ($permIds as $permId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id'       => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('name', ['zoom.view', 'google_sheets.view'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
