<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sustituye los campos de texto libre de pics_followups (cognition_tool/result,
 * anxiety_tool/result, etc.) por los instrumentos validados ya en uso en el proyecto
 * "Panel de control" (Pfeiffer/AMT, MoCA, HADS-A, PHQ-9, PC-PTSD-5, PTG-SF, PICS-F),
 * y agrega el puntaje de riesgo PICS de 7 factores a pics_cases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pics_followups', function (Blueprint $table) {
            $table->dropColumn([
                'cognition_tool', 'cognition_result', 'cognition_positive',
                'anxiety_tool', 'anxiety_result', 'anxiety_positive',
                'depression_tool', 'depression_result', 'depression_positive',
                'ptsd_tool', 'ptsd_result', 'ptsd_positive',
                'quality_of_life_tool', 'quality_of_life_result',
                'caregiver_burden_tool', 'caregiver_burden_result',
                'fatigue', 'pain',
            ]);
        });

        Schema::table('pics_followups', function (Blueprint $table) {
            $table->string('respondent_type', 10)->default('paciente')->after('checkpoint'); // paciente | familia
            $table->string('disfagia', 20)->nullable(); // pasa | falla | no_aplica | pendiente

            $table->json('amt_respuestas')->nullable(); // Pfeiffer/AMT: 10 booleanos (correcto/incorrecto)
            $table->unsignedTinyInteger('amt_score')->nullable(); // errores, 0-10

            $table->json('moca_respuestas')->nullable(); // 7 dominios, solo si AMT >= 3 errores
            $table->unsignedTinyInteger('moca_total')->nullable();

            $table->json('hads_respuestas')->nullable(); // HADS-A: 7 ítems 0-3
            $table->unsignedTinyInteger('hads_ansiedad')->nullable();

            $table->json('phq9_respuestas')->nullable(); // PHQ-9: 9 ítems 0-3
            $table->unsignedTinyInteger('phq9_score')->nullable();

            $table->json('pcptsd_respuestas')->nullable(); // PC-PTSD-5: 5 ítems sí/no
            $table->unsignedTinyInteger('pcptsd_score')->nullable();

            $table->json('ptg_respuestas')->nullable(); // PTG-SF: 10 ítems 0-5, solo checkpoints tardíos
            $table->unsignedTinyInteger('ptg_score')->nullable();

            $table->json('picsf_respuestas')->nullable(); // PICS-F (cuidador): 5 ítems 0-4
            $table->unsignedTinyInteger('picsf_distress')->nullable();

            $table->decimal('fatigue_score', 3, 1)->nullable(); // NRS 0-10
            $table->decimal('pain_rest', 3, 1)->nullable(); // NRS 0-10
            $table->decimal('pain_movement', 3, 1)->nullable(); // NRS 0-10

            // Origen del registro: null = lo diligenció el profesional; si no, queda
            // pendiente de confirmación (mismo patrón reportado/validado de las metas).
            $table->string('submitted_by_type')->nullable();
            $table->unsignedBigInteger('submitted_by_id')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
        });

        Schema::table('pics_cases', function (Blueprint $table) {
            $table->unsignedTinyInteger('age_at_admission')->nullable();
            $table->decimal('barthel_at_discharge', 5, 1)->nullable();
            $table->boolean('shock_or_sepsis')->nullable();
            $table->unsignedTinyInteger('mrc_total')->nullable(); // suma 12 grupos musculares, 0-60
            $table->decimal('handgrip_kg', 5, 1)->nullable(); // máximo de ambas manos
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->string('risk_level', 10)->nullable(); // bajo | medio | alto
            $table->json('risk_factors')->nullable(); // bitácora de por qué se sumó cada punto
        });
    }

    public function down(): void
    {
        Schema::table('pics_cases', function (Blueprint $table) {
            $table->dropColumn(['age_at_admission', 'barthel_at_discharge', 'shock_or_sepsis', 'mrc_total', 'handgrip_kg', 'risk_score', 'risk_level', 'risk_factors']);
        });

        // SQLite exige quitar la llave foránea antes de poder eliminar la columna que la porta.
        Schema::table('pics_followups', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
        });

        Schema::table('pics_followups', function (Blueprint $table) {
            $table->dropColumn([
                'respondent_type', 'disfagia',
                'amt_respuestas', 'amt_score', 'moca_respuestas', 'moca_total',
                'hads_respuestas', 'hads_ansiedad', 'phq9_respuestas', 'phq9_score',
                'pcptsd_respuestas', 'pcptsd_score', 'ptg_respuestas', 'ptg_score',
                'picsf_respuestas', 'picsf_distress',
                'fatigue_score', 'pain_rest', 'pain_movement',
                'submitted_by_type', 'submitted_by_id', 'confirmed_by', 'confirmed_at',
            ]);
        });

        Schema::table('pics_followups', function (Blueprint $table) {
            $table->string('cognition_tool')->nullable();
            $table->string('cognition_result')->nullable();
            $table->boolean('cognition_positive')->nullable();
            $table->string('anxiety_tool')->nullable();
            $table->string('anxiety_result')->nullable();
            $table->boolean('anxiety_positive')->nullable();
            $table->string('depression_tool')->nullable();
            $table->string('depression_result')->nullable();
            $table->boolean('depression_positive')->nullable();
            $table->string('ptsd_tool')->nullable();
            $table->string('ptsd_result')->nullable();
            $table->boolean('ptsd_positive')->nullable();
            $table->string('quality_of_life_tool')->nullable();
            $table->string('quality_of_life_result')->nullable();
            $table->string('caregiver_burden_tool')->nullable();
            $table->string('caregiver_burden_result')->nullable();
            $table->string('fatigue')->nullable();
            $table->string('pain')->nullable();
        });
    }
};
