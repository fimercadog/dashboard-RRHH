<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'whatsapp.manage', // configurar WhatsApp/Meta (settings)
        'ai.manage',       // configurar proveedor de IA
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Super Admin y Administrador de empresa obtienen ambos permisos.
        $adminRoles = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Administrador de empresa'])
            ->pluck('id');

        foreach ($adminRoles as $roleId) {
            foreach ($this->permissions as $perm) {
                $permId = DB::table('permissions')->where('name', $perm)->value('id');
                if ($permId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->permissions)->delete();
    }
};
