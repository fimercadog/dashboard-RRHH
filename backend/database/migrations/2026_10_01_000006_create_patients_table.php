<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('species_id')->constrained('species')->restrictOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
            $table->string('name', 100);
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('color', 60)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('microchip', 40)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'species_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
