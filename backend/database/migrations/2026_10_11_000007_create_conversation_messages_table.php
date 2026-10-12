<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('content')->nullable();
            $table->enum('content_type', ['text', 'image', 'document', 'audio', 'video', 'template'])->default('text');
            $table->enum('sender_type', ['contact', 'agent', 'ai', 'system'])->default('contact');
            $table->string('meta_message_id', 100)->unique();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('meta_media_id')->nullable();
            $table->string('meta_mime_type')->nullable();
            $table->text('meta_caption')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};
