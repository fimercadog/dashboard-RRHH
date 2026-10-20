<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Índice compuesto para GET /employees/selector:
            // WHERE company_id = ? AND employment_status = 'active' ORDER BY first_name LIMIT 30
            $table->index(['company_id', 'employment_status', 'first_name'], 'employees_selector_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_selector_idx');
        });
    }
};
