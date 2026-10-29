<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_safety_events', function (Blueprint $table) {
            $table->string('event_category', 30)->default('incident')->after('event_type');
            $table->foreignId('assessment_finding_id')->nullable()->after('sepsis_bundle_task_id')
                ->constrained('assessment_findings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_safety_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_finding_id');
        });

        Schema::table('sepsis_safety_events', function (Blueprint $table) {
            $table->dropColumn('event_category');
        });
    }
};
