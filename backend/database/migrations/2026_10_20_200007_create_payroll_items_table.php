<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('employee_contracts')->cascadeOnDelete();

            $table->decimal('salary_snapshot', 12, 2);
            $table->enum('salary_modality_snapshot', ['ordinary', 'integral']);
            $table->decimal('ibc', 12, 2);

            $table->decimal('worked_days', 5, 2);
            $table->decimal('vacation_days', 5, 2)->default(0);
            $table->decimal('sick_days_common', 5, 2)->default(0);
            $table->decimal('sick_days_labor', 5, 2)->default(0);
            $table->decimal('unpaid_days', 5, 2)->default(0);

            $table->decimal('salary_earned', 12, 2);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('vacation_pay', 10, 2)->default(0);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('other_earnings', 10, 2)->default(0);
            $table->decimal('total_earned', 14, 2);

            $table->decimal('health_employee', 10, 2);
            $table->decimal('pension_employee', 10, 2);
            $table->decimal('solidarity_fund', 10, 2)->default(0);
            $table->decimal('withholding_tax', 10, 2)->default(0);
            $table->decimal('loan_deductions', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 14, 2);

            $table->decimal('net_pay', 14, 2);

            $table->decimal('health_employer', 10, 2);
            $table->decimal('pension_employer', 10, 2);
            $table->decimal('arl_employer', 10, 2);
            $table->decimal('ccf_employer', 10, 2);
            $table->decimal('sena_employer', 10, 2)->default(0);
            $table->decimal('icbf_employer', 10, 2)->default(0);
            $table->boolean('parafiscal_exempt')->default(false);
            $table->string('parafiscal_exempt_basis', 300)->nullable();
            $table->decimal('total_employer_cost', 14, 2);

            $table->decimal('severance_provision', 10, 2)->default(0);
            $table->decimal('interest_provision', 10, 2)->default(0);
            $table->decimal('prima_provision', 10, 2)->default(0);
            $table->decimal('vacation_provision', 10, 2)->default(0);

            $table->enum('status', ['calculated', 'approved', 'paid'])->default('calculated');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['company_id', 'payroll_run_id']);
            $table->index(['company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
