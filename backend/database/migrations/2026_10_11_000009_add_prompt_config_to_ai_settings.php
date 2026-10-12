<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->text('system_prompt')->nullable()->after('base_url');
            $table->unsignedSmallInteger('context_messages')->default(10)->after('system_prompt');
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn(['system_prompt', 'context_messages']);
        });
    }
};
