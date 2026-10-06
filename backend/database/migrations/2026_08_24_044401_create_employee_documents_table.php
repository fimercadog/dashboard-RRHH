<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('name');
            $table->string('file_path');
            $table->date('issue_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('status')->default('valid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'expiration_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
