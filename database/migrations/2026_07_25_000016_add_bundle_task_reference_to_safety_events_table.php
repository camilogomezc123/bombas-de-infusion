<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_safety_events', function (Blueprint $table) {
            $table->foreignId('sepsis_bundle_task_id')->nullable()->after('sepsis_case_id')
                ->constrained('sepsis_bundle_tasks')->nullOnDelete();
            $table->dateTime('event_at')->nullable()->after('occurred_on');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_safety_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sepsis_bundle_task_id');
            $table->dropColumn('event_at');
        });
    }
};
