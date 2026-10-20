<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('period_type', ['monthly', 'biweekly', 'weekly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date');
            $table->enum('status', ['draft', 'processing', 'closed', 'cancelled'])->default('draft');
            $table->boolean('is_complementary')->default(false);
            $table->foreignId('original_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->boolean('authorized_reversal')->default(false);
            $table->string('reversal_reason', 500)->nullable();
            $table->foreignId('reversal_authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversal_authorized_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
