<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('segment_clients', function (Blueprint $table) {
            $table->foreignId('segment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->primary(['segment_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_clients');
        Schema::dropIfExists('segments');
    }
};
