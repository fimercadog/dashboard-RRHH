<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense', 'cost']);
            $table->enum('nature', ['debit', 'credit']);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('allows_movements')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'start_date', 'end_date']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('number', 30);
            $table->date('date');
            $table->string('description', 200);
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_of_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'accounting_period_id']);
            $table->index(['company_id', 'reference_type', 'reference_id']);
            $table->index(['company_id', 'date']);
            $table->index('status');
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('description', 200)->nullable();
            $table->decimal('debit', 14, 4)->default(0);
            $table->decimal('credit', 14, 4)->default(0);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
        });

        Schema::create('accounting_account_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('config_key', 60);
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'config_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_account_configs');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('chart_of_accounts');
    }
};
