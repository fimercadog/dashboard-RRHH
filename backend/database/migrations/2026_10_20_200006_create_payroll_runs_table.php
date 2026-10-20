<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->unsignedTinyInteger('run_number')->default(1);
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'approved'])->default('pending');
            $table->unsignedSmallInteger('total_employees')->default(0);
            $table->decimal('total_devengado', 14, 2)->default(0);
            $table->decimal('total_deducciones', 14, 2)->default(0);
            $table->decimal('total_neto', 14, 2)->default(0);
            $table->decimal('total_aportes_empresa', 14, 2)->default(0);
            $table->json('parameters_snapshot');
            $table->foreignId('tax_profile_id')->nullable()->constrained('company_tax_profiles')->nullOnDelete();
            $table->text('error_log')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'payroll_period_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
