<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'audit_logs_company_created_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'stock_movements_company_created_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'purchase_orders_company_status_index');
            $table->index(['company_id', 'created_at'], 'purchase_orders_company_created_index');
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'sale_orders_company_status_index');
            $table->index(['company_id', 'created_at'], 'sale_orders_company_created_index');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'quotes_company_status_index');
            $table->index(['company_id', 'created_at'], 'quotes_company_created_index');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->index(['company_id', 'stage'], 'deals_company_stage_index');
            $table->index(['company_id', 'owner_id'], 'deals_company_owner_index');
            $table->index(['company_id', 'created_at'], 'deals_company_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_company_created_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_company_created_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_company_status_index');
            $table->dropIndex('purchase_orders_company_created_index');
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropIndex('sale_orders_company_status_index');
            $table->dropIndex('sale_orders_company_created_index');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_company_status_index');
            $table->dropIndex('quotes_company_created_index');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('deals_company_stage_index');
            $table->dropIndex('deals_company_owner_index');
            $table->dropIndex('deals_company_created_index');
        });
    }
};
