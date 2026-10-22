<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();

            // Spatie crea unique(name, guard_name); al añadir company_id hay que
            // reemplazarlo para que dos empresas puedan tener roles del mismo nombre.
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['company_id', 'name', 'guard_name']);

            $table->index(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id', 'name', 'guard_name']);
            $table->dropIndex(['company_id', 'name']);
            $table->dropColumn('company_id');

            $table->unique(['name', 'guard_name']);
        });
    }
};
