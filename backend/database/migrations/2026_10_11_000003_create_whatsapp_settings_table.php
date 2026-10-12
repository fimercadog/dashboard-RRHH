<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();          // cifrado en Model
            $table->string('webhook_verify_token')->nullable(); // token público para verificación de webhook
            $table->string('waba_id')->nullable();              // WhatsApp Business Account ID
            $table->boolean('is_active')->default(false);
            $table->timestamp('webhook_verified_at')->nullable();
            $table->timestamps();

            $table->unique('company_id');
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
