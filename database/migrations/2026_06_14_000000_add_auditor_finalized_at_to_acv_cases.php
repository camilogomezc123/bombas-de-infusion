<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acv_cases', function (Blueprint $table) {
            // Momento en que el auditor finaliza su análisis (congela el cronómetro).
            $table->timestamp('auditor_finalized_at')->nullable()->after('analysis_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('acv_cases', function (Blueprint $table) {
            $table->dropColumn('auditor_finalized_at');
        });
    }
};
