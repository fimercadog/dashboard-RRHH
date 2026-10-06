<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'species_id', 'name']);
            $table->index(['company_id', 'species_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeds');
    }
};
