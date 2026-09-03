<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('employee_count')->nullable();
            $table->string('priority_module')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->default('contact'); // contact | demo
            $table->string('status')->default('new');      // new | contacted | discarded
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
