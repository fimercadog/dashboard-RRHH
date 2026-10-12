<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_setting_id')->constrained('whatsapp_settings')->cascadeOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone');
            $table->string('channel')->default('whatsapp');
            $table->enum('status', ['open', 'transferred', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_customer_msg_at')->nullable();
            $table->string('meta_conversation_id')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_setting_id', 'contact_phone']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
