<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('legal_nature', 100);
            $table->string('tax_contributor_type', 100);
            $table->boolean('parafiscal_exempt')->default(false);
            $table->text('exempt_basis')->nullable();
            $table->string('verified_by', 200)->nullable();
            $table->date('verified_at')->nullable();
            $table->enum('review_status', ['pending', 'validated', 'requires_professional_review'])->default('pending');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'valid_from']);
            $table->index(['company_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_tax_profiles');
    }
};
