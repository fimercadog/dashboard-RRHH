<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained('payroll_items')->cascadeOnDelete();
            $table->enum('line_type', ['earning', 'deduction', 'employer_contribution', 'provision']);
            $table->string('code', 60);
            $table->string('description', 200);
            $table->decimal('quantity', 8, 4)->nullable();
            $table->decimal('unit_value', 12, 4)->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payroll_item_id', 'line_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_lines');
    }
};
