<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contingency_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->json('enabled_modules');
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at');
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('status')->default('active'); // active | closed
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('contingency_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contingency_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // activated | deactivated
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contingency_events');
        Schema::dropIfExists('contingency_sessions');
    }
};
