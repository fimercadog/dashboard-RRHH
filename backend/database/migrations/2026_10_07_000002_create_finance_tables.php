<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['cash', 'bank', 'savings'])->default('cash');
            $table->string('currency', 3)->default('COP');
            $table->decimal('balance', 14, 4)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_account_id')->constrained('cash_accounts');
            $table->foreignId('to_account_id')->constrained('cash_accounts');
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 14, 4);
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();
            $table->index(['company_id', 'date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_account_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('payable_type', 30);  // 'accounts_receivable' | 'accounts_payable'
            $table->unsignedBigInteger('payable_id');
            $table->decimal('amount', 14, 4);
            $table->date('date');
            $table->enum('method', ['cash', 'transfer', 'check', 'card', 'other'])->default('cash');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->unsignedBigInteger('journal_entry_id')->nullable();  // gancho Fase F
            $table->timestamps();
            $table->index(['company_id', 'payable_type', 'payable_id']);
            $table->index(['company_id', 'cash_account_id']);
            $table->index('date');
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_account_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['income', 'expense', 'transfer_in', 'transfer_out']);
            $table->decimal('amount', 14, 4);
            $table->date('date');
            $table->string('reference_type', 50);  // 'payment' | 'transfer'
            $table->unsignedBigInteger('reference_id');
            $table->string('description', 200);
            $table->timestamps();
            $table->index(['company_id', 'cash_account_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('cash_accounts');
    }
};
