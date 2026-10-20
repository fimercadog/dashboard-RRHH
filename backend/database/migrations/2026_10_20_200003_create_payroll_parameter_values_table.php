<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_parameter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('payroll_parameters')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('value', 14, 4);
            $table->string('unit', 30);
            $table->string('source_norm', 300);
            $table->string('source_url', 500)->nullable();
            $table->date('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['parameter_id', 'effective_from']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_parameter_values');
    }
};
