<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clinical_programs')->updateOrInsert(
            ['code' => 'PICS'],
            [
                'name' => 'Programa Institucional de Seguimiento del Síndrome Post Cuidado Intensivo',
                'short_name' => 'PICS',
                'description' => 'Programa de seguimiento longitudinal de sobrevivientes de cuidado crítico, orientado a detectar y gestionar las secuelas físicas, cognitivas y psicológicas del síndrome post cuidado intensivo (PICS) tras el egreso.',
                'purpose' => 'Identificar tempranamente el deterioro funcional, cognitivo y psicológico posterior a una estancia crítica, y garantizar la remisión oportuna a los servicios de rehabilitación y salud mental correspondientes.',
                'target_population' => 'Pacientes adultos egresados de unidades de cuidado crítico, con énfasis en quienes tuvieron ventilación mecánica prolongada, delirium o sedación profunda durante la estancia.',
                'scope' => 'Desde el egreso de la unidad de cuidado crítico (o de remisión externa equivalente) hasta el cierre del seguimiento a 12 meses, en los checkpoints 48-72h, 7d, 30d, 3m, 6m y 12m.',
                'status' => 'implementation',
                'version' => '0.1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $programId = DB::table('clinical_programs')->where('code', 'PICS')->value('id');

        Schema::create('pics_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('icu_stay_id')->nullable()->constrained('icu_stays')->nullOnDelete();
            $table->foreignId('assigned_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('case_number')->nullable();
            $table->unsignedBigInteger('case_sequence')->nullable();
            $table->string('status', 40)->default('assigned')->index();
            $table->string('month', 7)->nullable()->index();
            $table->dateTime('enrollment_at')->nullable();
            $table->string('enrollment_source', 40)->nullable();
            $table->decimal('mechanical_ventilation_days', 8, 3)->nullable();
            $table->decimal('delirium_days', 8, 3)->nullable();
            $table->decimal('icu_los_days', 8, 3)->nullable();
            $table->decimal('sedation_deep_days', 8, 3)->nullable();
            $table->json('clinical_data')->nullable();
            $table->json('field_status')->nullable();
            $table->boolean('is_valid')->default(true)->index();
            $table->boolean('is_cancelled')->default(false)->index();
            $table->string('cancellation_reason')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('analysis_started_at')->nullable();
            $table->dateTime('auditor_finalized_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pics_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->string('checkpoint'); // 48_72h | 7d | 30d | 3m | 6m | 12m
            $table->string('contact_method')->nullable();
            $table->boolean('contact_achieved')->default(false);
            $table->string('functional_capacity')->nullable();
            $table->string('mobility')->nullable();
            $table->string('strength')->nullable();
            $table->string('fatigue')->nullable();
            $table->string('pain')->nullable();
            $table->string('sleep_quality')->nullable();
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
            $table->text('medications_review')->nullable();
            $table->boolean('readmission')->nullable();
            $table->boolean('return_to_work')->nullable();
            $table->string('quality_of_life_tool')->nullable();
            $table->string('quality_of_life_result')->nullable();
            $table->string('caregiver_burden_tool')->nullable();
            $table->string('caregiver_burden_result')->nullable();
            $table->json('referred_to')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('followed_up_at')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pics_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pics_followup_id')->nullable()->constrained('pics_followups')->nullOnDelete();
            $table->string('specialty');
            $table->string('status', 20)->default('open')->index(); // open | scheduled | completed | no_show
            $table->dateTime('referred_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Ficha técnica de indicadores. Los marcados is_core_indicator=true se calculan
        // en vivo por PicsIndicatorService (summary()/dashboard()). Sin meta institucional
        // todavía: se asigna cuando el Comité PICS apruebe la línea base.
        $coreIndicators = [
            ['code' => 'PICS-01', 'name' => 'Tamizaje positivo para PICS (cognitivo, psicológico o físico)', 'key' => 'screening_positive_pct', 'group' => 'result'],
            ['code' => 'PICS-05', 'name' => 'Seguimiento post-UCI iniciado', 'key' => 'followup_rate_pct', 'group' => 'process'],
            ['code' => 'PICS-07', 'name' => 'Remisiones completadas', 'key' => 'referral_completion_pct', 'group' => 'process'],
        ];

        foreach ($coreIndicators as $index => $indicator) {
            DB::table('indicator_definitions')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $indicator['code']],
                [
                    'name' => $indicator['name'],
                    'indicator_group' => $indicator['group'],
                    'is_core_indicator' => true,
                    'core_indicator_key' => $indicator['key'],
                    'source' => 'Módulo de Indicadores (PicsIndicatorService)',
                    'periodicity' => 'Mensual',
                    'sort_order' => $index + 1,
                    'observations' => 'Sin meta institucional asignada todavía; pendiente de aprobación por el Comité PICS tras construir línea base.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pics_referrals');
        Schema::dropIfExists('pics_followups');
        Schema::dropIfExists('pics_cases');

        $programId = DB::table('clinical_programs')->where('code', 'PICS')->value('id');
        DB::table('clinical_rules')->where('clinical_program_id', $programId)->delete();
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->delete();
        DB::table('clinical_programs')->where('id', $programId)->delete();
    }
};
