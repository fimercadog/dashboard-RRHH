<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number', 50);
            $table->enum('contract_type', ['indefinido', 'fijo', 'obra_labor', 'aprendizaje', 'prestacion_servicios']);
            $table->enum('salary_modality', ['ordinary', 'integral'])->default('ordinary');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2);
            $table->enum('salary_type', ['monthly', 'daily', 'hourly'])->default('monthly');
            $table->unsignedTinyInteger('work_hours_per_week')->default(48);
            $table->unsignedTinyInteger('risk_class')->default(1);
            $table->enum('status', ['active', 'terminated', 'suspended'])->default('active');
            $table->string('termination_reason', 200)->nullable();
            $table->date('terminated_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'contract_number']);
            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
