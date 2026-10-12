<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('openai'); // openai | anthropic | openai_compat
            $table->string('model')->nullable();
            $table->text('api_key')->nullable();           // cifrado en Model
            $table->string('base_url')->nullable();        // para proveedores openai_compat
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->unique('company_id');
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
