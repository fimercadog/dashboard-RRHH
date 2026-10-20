<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('scope', ['global', 'company'])->default('global');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'company_id']);
            $table->index(['scope', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_parameters');
    }
};
