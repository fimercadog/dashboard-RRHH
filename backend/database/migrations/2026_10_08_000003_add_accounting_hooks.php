<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ganchos en documentos existentes para trazabilidad contable.
        // NO modifica lógica existente — solo agrega columnas nullable.

        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('user_id')
                ->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('user_id')
                ->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('status')
                ->constrained('journal_entries')->nullOnDelete();
        });

        // cash_accounts.account_id: FK al plan de cuentas (ej. Caja 1105, Banco 1110).
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->foreignId('accounting_account_id')->nullable()->after('notes')
                ->constrained('chart_of_accounts')->nullOnDelete();
        });

        // D-CON-6: FKs directas en products para cuentas contables.
        // Los campos *_account_code varchar(20) ya existentes se mantienen (no romper datos).
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('inventory_account_id')->nullable()->after('inventory_account_code')
                ->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->after('cogs_account_code')
                ->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('sale_account_id')->nullable()->after('sale_account_code')
                ->constrained('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['inventory_account_id']);
            $table->dropForeign(['cogs_account_id']);
            $table->dropForeign(['sale_account_id']);
            $table->dropColumn(['inventory_account_id', 'cogs_account_id', 'sale_account_id']);
        });

        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropColumn('journal_entry_id');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropColumn('journal_entry_id');
        });

        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropColumn('journal_entry_id');
        });
    }
};
