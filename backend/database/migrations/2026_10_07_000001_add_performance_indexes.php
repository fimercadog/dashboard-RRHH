<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['company_id', 'employment_status'], 'employees_company_status_index');
            $table->index(['company_id', 'created_at'], 'employees_company_created_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'clients_company_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_company_status_index');
            $table->dropIndex('employees_company_created_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_company_created_index');
        });
    }
};
