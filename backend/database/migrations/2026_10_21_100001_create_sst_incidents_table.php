<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sst_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->date('incident_date');
            $table->enum('type', ['near_miss', 'incident', 'accident', 'occupational_disease']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('location', 300)->nullable();
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->integer('days_lost')->default(0);
            $table->enum('status', ['reported', 'investigating', 'resolved', 'closed'])->default('reported');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'incident_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sst_incidents');
    }
};
