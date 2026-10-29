<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->boolean('goals_of_care_limitation')->nullable()->after('special_population');
            $table->text('goals_of_care_notes')->nullable()->after('goals_of_care_limitation');
            $table->string('caregiver_name')->nullable()->after('goals_of_care_notes');
            $table->string('caregiver_relationship')->nullable()->after('caregiver_name');
            $table->dateTime('caregiver_identified_at')->nullable()->after('caregiver_relationship');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropColumn([
                'goals_of_care_limitation', 'goals_of_care_notes',
                'caregiver_name', 'caregiver_relationship', 'caregiver_identified_at',
            ]);
        });
    }
};
