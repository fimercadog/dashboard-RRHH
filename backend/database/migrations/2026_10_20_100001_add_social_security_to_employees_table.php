<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('eps')->nullable()->after('work_schedule');
            $table->string('arl')->nullable()->after('eps');
            $table->string('pension_fund')->nullable()->after('arl');
            $table->string('compensation_fund')->nullable()->after('pension_fund');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['eps', 'arl', 'pension_fund', 'compensation_fund']);
        });
    }
};
