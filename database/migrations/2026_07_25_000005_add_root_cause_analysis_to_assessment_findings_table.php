<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->text('why_1')->nullable()->after('description');
            $table->text('why_2')->nullable()->after('why_1');
            $table->text('why_3')->nullable()->after('why_2');
            $table->text('why_4')->nullable()->after('why_3');
            $table->text('why_5')->nullable()->after('why_4');
            $table->text('root_cause')->nullable()->after('why_5');
            $table->boolean('validation_explains_gap')->default(false)->after('root_cause');
            $table->boolean('validation_logical_continuity')->default(false)->after('validation_explains_gap');
            $table->boolean('validation_no_repeated_causes')->default(false)->after('validation_logical_continuity');
            $table->boolean('validation_not_symptom')->default(false)->after('validation_no_repeated_causes');
            $table->boolean('validation_intervention_reduces_gap')->default(false)->after('validation_not_symptom');
            $table->boolean('validation_not_only_consequence')->default(false)->after('validation_intervention_reduces_gap');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_findings', function (Blueprint $table) {
            $table->dropColumn([
                'why_1', 'why_2', 'why_3', 'why_4', 'why_5', 'root_cause',
                'validation_explains_gap', 'validation_logical_continuity',
                'validation_no_repeated_causes', 'validation_not_symptom',
                'validation_intervention_reduces_gap', 'validation_not_only_consequence',
            ]);
        });
    }
};
