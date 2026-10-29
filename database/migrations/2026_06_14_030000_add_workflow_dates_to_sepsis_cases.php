<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->timestamp('analysis_started_at')->nullable()->after('assigned_at');
            $table->timestamp('auditor_finalized_at')->nullable()->after('analysis_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropColumn(['analysis_started_at', 'auditor_finalized_at']);
        });
    }
};
