<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'products_company_status_index');
            $table->index(['company_id', 'created_at'], 'products_company_created_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index(['company_id', 'identification_number'], 'clients_company_idnum_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_company_status_index');
            $table->dropIndex('products_company_created_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_company_idnum_index');
        });
    }
};
