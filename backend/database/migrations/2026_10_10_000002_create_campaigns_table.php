<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('type')->default('mass'); // individual|mass|announcement|reminder|campaign
            $table->string('status')->default('draft'); // draft|scheduled|sent|cancelled
            $table->string('audience_source'); // erp_employees|erp_clients|erp_leads|csv|google_sheets
            $table->json('audience_filters')->nullable();
            $table->string('message_subject')->nullable();
            $table->text('message_body')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
