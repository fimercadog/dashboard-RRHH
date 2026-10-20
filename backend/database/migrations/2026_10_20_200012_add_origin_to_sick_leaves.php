<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sick_leaves', function (Blueprint $table) {
            $table->enum('origin', ['common', 'labor'])->default('common')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('sick_leaves', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
