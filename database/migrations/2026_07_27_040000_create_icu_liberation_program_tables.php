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
            ['code' => 'ICULIB'],
            [
                'name' => 'Programa Institucional de Excelencia en ICU Liberation, Recuperación del Paciente Crítico y Prevención del Síndrome Post-UCI',
                'short_name' => 'ICU Liberation',
                'description' => 'Programa clínico transversal aplicado a todos los pacientes elegibles de las unidades de cuidado crítico institucionales.',
                'purpose' => 'Reducir el daño iatrogénico, la ventilación mecánica innecesaria, la sedación profunda, el delirium, la inmovilidad, las restricciones físicas y las secuelas del síndrome post-UCI mediante la aplicación interdisciplinaria, segura, humanizada y medible del bundle ABCDEF.',
                'target_population' => 'Pacientes adultos elegibles atendidos en UCI de adultos, cuidado intermedio, UCI cardiovascular, quirúrgica, neurológica u oncológica. La población pediátrica queda fuera de alcance hasta habilitar un módulo específico.',
                'scope' => 'Desde el ingreso a UCI y la evaluación inicial hasta el traslado, el egreso hospitalario y el seguimiento del síndrome post-UCI (PICS).',
                'status' => 'implementation',
                'version' => '0.1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $programId = DB::table('clinical_programs')->where('code', 'ICULIB')->value('id');

        // Ficha técnica de indicadores. Los marcados is_core_indicator=true se calculan
        // en vivo por IcuLiberationIndicatorService (dashboard()). El resto queda catalogado
        // como referencia del alcance institucional completo, pendiente de fuente automática
        // o de aprobación de meta (no se les atribuye una meta que el manual no defina).
        $liveIndicators = [
            ['COMP-01', 'Cumplimiento del bundle ABCDEF', 'bundle_compliance_pct', 'process', null,
                'Componentes marcados "no aplica" en la estancia (field_status) — no se cuentan como incumplimiento.',
                'Cálculo automático desde los registros del componente; el usuario solo confirma elegibilidad y exclusiones.'],
            ['A-01', 'Dolor evaluado', 'pain_assessment_pct', 'process', null,
                'Ninguna (el dolor debe evaluarse en todo paciente crítico, con o sin capacidad de autorreporte).',
                'Cálculo automático desde `icu_pain_assessments`.'],
            ['B-02', 'SAT realizado entre elegibles', 'sat_realized_pct', 'process', null,
                'Estancias no ventiladas; estancias con exclusión documentada en `icu_sat_trials.exclusion_reason` o marcadas "no aplica" en la estancia.',
                'Cálculo automático; requiere que la exclusión quede documentada, no un campo vacío.'],
            ['B-06', 'SBT realizado entre elegibles', 'sbt_realized_pct', 'process', null,
                'Estancias no ventiladas; estancias con exclusión documentada en `icu_sbt_trials.exclusion_reason` o marcadas "no aplica" en la estancia.',
                'Cálculo automático; requiere que la exclusión quede documentada, no un campo vacío.'],
            ['C-01', 'Objetivo de sedación documentado', 'sedation_goal_documented_pct', 'process', null,
                'Ninguna.',
                'Cálculo automático desde `icu_sedation_assessments.goal_value`.'],
            ['D-01', 'Evaluación de delirium realizada', 'delirium_assessment_pct', 'process', null,
                'Ninguna (incluye evaluaciones con `assessable=false` y estado de coma documentado).',
                'Cálculo automático desde `icu_delirium_assessments`.'],
            ['D-05', 'Prevalencia diaria de delirium', 'delirium_prevalence_pct', 'result', null,
                'Estancias sin evaluación de delirium registrada (no se asume negativo por ausencia de dato).',
                'Cálculo automático; denominador = estancias con al menos una evaluación.'],
            ['E-03', 'Movilidad realizada entre elegibles', 'mobility_realized_pct', 'process', null,
                'Estancias con tamizaje de seguridad no aprobado y barrera documentada, o marcadas "no aplica" en la estancia.',
                'Cálculo automático; requiere `safety_screen_passed` y barrera documentados, no un campo vacío.'],
            ['RES-ICUL-03', 'Días de ventilación mecánica (mediana)', 'ventilation_days_median', 'result', null,
                'Estancias sin `ventilation_start_at` registrado.',
                'Mediana calculada sobre estancias con fecha de inicio y fin de ventilación documentadas.'],
            ['RES-ICUL-05', 'Estancia en UCI (mediana, días)', 'icu_los_median_days', 'result', null,
                'Estancias sin fecha de ingreso ni de egreso de UCI documentadas.',
                'Usa el campo manual `icu_stay_days` si existe; si no, se deriva de `admission_at`/`icu_discharge_at`.'],
            ['RES-ICUL-01', 'Mortalidad hospitalaria', 'hospital_mortality_pct', 'result', null,
                'Ninguna.',
                'Cálculo automático desde `death_at`.'],
            ['RES-ICUL-02', 'Mortalidad en UCI', 'icu_mortality_pct', 'result', null,
                'Ninguna.',
                'Cálculo automático: `death_at` no nulo y `icu_discharge_at` nulo (fallece sin haber egresado de UCI).'],
            ['RES-ICUL-10', 'Reintubación', 'reintubation_pct', 'result', null,
                'Estancias sin extubación documentada (no aplica el concepto de reintubación).',
                'Cálculo automático; denominador = estancias con `extubation_at` registrado.'],
            ['RES-ICUL-20', 'Autoextubación', 'unplanned_extubation_pct', 'result', null,
                'Estancias no ventiladas.',
                'Cálculo automático desde `unplanned_extubation`.'],
            ['PICS-05', 'Seguimiento post-UCI iniciado', 'pics_followup_rate_pct', 'process', null,
                'Estancias fallecidas antes del egreso.',
                'Cálculo automático; denominador = estancias egresadas de UCI u hospital.'],
        ];
        foreach ($liveIndicators as $index => [$code, $name, $key, $group, $target, $exclusionCriteria, $validationMethod]) {
            DB::table('indicator_definitions')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $name,
                    'indicator_group' => $group,
                    'unit' => '%',
                    'target_value' => $target,
                    'expected_direction' => in_array($key, ['delirium_prevalence_pct', 'hospital_mortality_pct', 'icu_mortality_pct', 'reintubation_pct', 'unplanned_extubation_pct', 'ventilation_days_median', 'icu_los_median_days'], true) ? 'lower_better' : 'higher_better',
                    'source' => 'Módulo ICU Liberation (IcuLiberationIndicatorService)',
                    'exclusion_criteria' => $exclusionCriteria,
                    'validation_method' => $validationMethod,
                    'periodicity' => 'Mensual',
                    'is_core_indicator' => true,
                    'core_indicator_key' => $key,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        // Catálogo ampliado (sin cálculo automático todavía): cubre el alcance de las
        // secciones 24-35 del programa para que la ficha técnica institucional esté
        // completa aunque la fuente de datos automática se incorpore progresivamente.
        $catalogedIndicators = [
            ['EST-ICUL-01', 'Comité ICU Liberation activo', 'structure'],
            ['EST-ICUL-02', 'Carta constitutiva aprobada', 'structure'],
            ['EST-ICUL-03', 'Cobertura interdisciplinaria por turno', 'structure'],
            ['EST-ICUL-04', 'Disponibilidad de escalas de dolor', 'structure'],
            ['EST-ICUL-05', 'Disponibilidad de RASS o SAS', 'structure'],
            ['EST-ICUL-06', 'Disponibilidad de CAM-ICU o ICDSC', 'structure'],
            ['EST-ICUL-07', 'Protocolo SAT/SBT vigente', 'structure'],
            ['EST-ICUL-12', 'Personal con competencia vigente', 'structure'],
            ['A-08', 'Exposición acumulada a opioides', 'process'],
            ['B-11', 'Extubación exitosa', 'result'],
            ['B-12', 'Reintubación a 48-72 horas', 'result'],
            ['C-04', 'Sedación profunda sin justificación', 'process'],
            ['C-07', 'Exposición a benzodiacepinas', 'process'],
            ['D-06', 'Días libres de delirium y coma', 'result'],
            ['E-06', 'Tiempo ingreso UCI-primera movilización', 'process'],
            ['E-10', 'Debilidad adquirida en UCI', 'result'],
            ['F-01', 'Familiar o cuidador principal identificado', 'process'],
            ['F-05', 'Reunión familiar realizada cuando corresponde', 'process'],
            ['SLP-01', 'Plan de sueño documentado', 'process'],
            ['RES-ICUL-12', 'Restricciones físicas', 'result'],
            ['RES-ICUL-17', 'Caídas', 'result'],
            ['PICS-01', 'Pacientes con riesgo de PICS identificado', 'process'],
            ['PICS-07', 'Función física evaluada en el seguimiento', 'process'],
            ['BAL-01', 'Autoextubación (equilibrio y seguridad)', 'result'],
            ['BAL-03', 'Caídas relacionadas con movilidad', 'result'],
        ];
        foreach ($catalogedIndicators as $index => [$code, $name, $group]) {
            DB::table('indicator_definitions')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $name,
                    'indicator_group' => $group,
                    'is_core_indicator' => false,
                    'periodicity' => 'Mensual',
                    'sort_order' => 100 + $index,
                    'observations' => 'Pendiente de fuente automática o de aprobación de meta institucional tras construir línea base.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        // Reglas clínicas configurables (borrador): requieren aprobación institucional
        // antes de activarse como reglas asistenciales, según la sección 4 del programa.
        foreach ([
            ['ICUL-SAT-01', 'Criterios de exclusión para la prueba de despertar espontáneo (SAT)'],
            ['ICUL-SBT-01', 'Criterios de exclusión para la prueba de respiración espontánea (SBT)'],
            ['ICUL-PAIN-01', 'Selección de escala de dolor según capacidad de autorreporte'],
            ['ICUL-DELIRIUM-01', 'Herramienta institucional para evaluación de delirium (CAM-ICU/ICDSC)'],
            ['ICUL-MOBILITY-01', 'Criterios de seguridad para movilidad temprana'],
        ] as [$code, $title]) {
            DB::table('clinical_rules')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $title,
                    'description' => 'Regla institucional en borrador; requiere aprobación clínica y validación contra el documento fuente antes de activarse.',
                    'version' => '0.1',
                    'status' => 'draft',
                    'source_document' => 'ICU Liberation / guía institucional pendiente de incorporar al repositorio documental',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::create('icu_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('unit_type')->default('adult'); // adult | intermediate | cardiovascular | surgical | neuro | oncologic | other
            $table->unsignedSmallInteger('bed_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('icu_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('icu_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('case_number')->unique();
            $table->unsignedBigInteger('case_sequence')->unique();
            $table->string('bed_label')->nullable();
            $table->string('admission_number')->nullable()->index();
            $table->string('admission_diagnosis')->nullable();
            $table->string('admission_source')->nullable();
            $table->string('status')->default('active')->index(); // active | discharged_icu | discharged_hospital | deceased | cancelled
            $table->dateTime('admission_at')->nullable()->index();
            $table->boolean('mechanical_ventilation')->default(false);
            $table->dateTime('ventilation_start_at')->nullable();
            $table->dateTime('extubation_at')->nullable();
            $table->boolean('unplanned_extubation')->nullable();
            $table->dateTime('reintubation_at')->nullable();
            $table->dateTime('tracheostomy_at')->nullable();
            $table->dateTime('icu_discharge_at')->nullable();
            $table->string('icu_discharge_destination')->nullable();
            $table->dateTime('hospital_discharge_at')->nullable();
            $table->dateTime('death_at')->nullable();
            $table->string('outcome_state')->nullable();
            $table->decimal('icu_stay_days', 8, 2)->nullable();
            $table->decimal('hospital_stay_days', 8, 2)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_cancelled')->default(false);
            $table->text('exclusion_reason')->nullable();
            $table->json('field_status')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_liberation_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->date('round_date');
            $table->json('participants')->nullable();
            $table->string('rass_sas_goal')->nullable();
            $table->string('rass_sas_actual')->nullable();
            $table->string('cam_icdsc_result')->nullable();
            $table->text('analgesics_sedatives')->nullable();
            $table->text('barriers_to_wakefulness')->nullable();
            $table->text('adjustment_plan')->nullable();
            $table->text('pain_summary')->nullable();
            $table->text('sat_plan')->nullable();
            $table->text('sbt_plan')->nullable();
            $table->text('delirium_summary')->nullable();
            $table->text('mobility_goal_text')->nullable();
            $table->boolean('restraints_reviewed')->default(false);
            $table->text('sleep_plan')->nullable();
            $table->text('family_engagement_notes')->nullable();
            $table->boolean('nutrition_reviewed')->default(false);
            $table->boolean('devices_reviewed')->default(false);
            $table->boolean('pics_risk_reviewed')->default(false);
            $table->text('transfer_plan')->nullable();
            $table->text('goals_of_day')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('icu_pain_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('assessed_at');
            $table->string('scale')->nullable(); // nrs | visual | cpot | bps | bps_ni | other
            $table->string('score')->nullable();
            $table->boolean('self_report_capable')->nullable();
            $table->string('location')->nullable();
            $table->string('pain_type')->nullable();
            $table->string('related_procedure')->nullable();
            $table->text('intervention')->nullable();
            $table->text('non_pharmacologic_intervention')->nullable();
            $table->dateTime('reassessed_at')->nullable();
            $table->string('score_after')->nullable();
            $table->text('adverse_event')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_sat_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('screened_at');
            $table->boolean('eligible')->default(false);
            $table->string('exclusion_reason')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->string('result')->nullable(); // success | fail | not_done
            $table->string('failure_reason')->nullable();
            $table->text('subsequent_action')->nullable();
            $table->text('adverse_event')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_sbt_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('screened_at');
            $table->boolean('eligible')->default(false);
            $table->string('exclusion_reason')->nullable();
            $table->string('initial_parameters')->nullable();
            $table->string('modality')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('result')->nullable(); // success | fail | not_done
            $table->string('failure_reason')->nullable();
            $table->boolean('extubation_assessed')->nullable();
            $table->text('adverse_event')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_sedation_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('assessed_at');
            $table->string('goal_scale')->nullable(); // rass | sas
            $table->string('goal_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->string('indication')->nullable();
            $table->text('analgesics')->nullable();
            $table->text('sedatives')->nullable();
            $table->text('infusions')->nullable();
            $table->text('boluses')->nullable();
            $table->boolean('nmb_used')->default(false);
            $table->text('deep_sedation_justification')->nullable();
            $table->string('withdrawal_risk')->nullable();
            $table->text('withdrawal_assessment')->nullable();
            $table->text('adverse_event')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_delirium_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('assessed_at');
            $table->string('tool')->nullable(); // cam_icu | icdsc | institutional
            $table->boolean('assessable')->default(true);
            $table->string('coma_state')->nullable();
            $table->string('result')->nullable(); // positive | negative | unable
            $table->string('subtype')->nullable();
            $table->text('precipitating_factors')->nullable();
            $table->text('associated_medications')->nullable();
            $table->json('interventions')->nullable();
            $table->dateTime('reassessment_at')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_mobility_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('performed_at');
            $table->boolean('safety_screen_passed')->default(false);
            $table->string('tool')->nullable(); // icu_mobility_scale | mrc | bmat | institutional
            $table->string('goal_level')->nullable();
            $table->string('baseline_level')->nullable();
            $table->string('achieved_level')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('distance_meters', 8, 1)->nullable();
            $table->string('participants')->nullable();
            $table->string('barrier')->nullable();
            $table->text('adverse_event')->nullable();
            $table->text('next_plan')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_family_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('occurred_at');
            $table->string('contact_name')->nullable();
            $table->string('relationship')->nullable();
            $table->string('communication_preferences')->nullable();
            $table->string('language')->nullable();
            $table->string('spiritual_support')->nullable();
            $table->boolean('participated_in_round')->default(false);
            $table->boolean('participated_in_mobility')->default(false);
            $table->boolean('participated_in_reorientation')->default(false);
            $table->boolean('education_provided')->default(false);
            $table->boolean('teach_back_confirmed')->default(false);
            $table->boolean('meeting_held')->default(false);
            $table->string('meeting_participants')->nullable();
            $table->text('meeting_objectives')->nullable();
            $table->text('information_provided')->nullable();
            $table->string('understanding_level')->nullable();
            $table->text('questions')->nullable();
            $table->text('values_preferences')->nullable();
            $table->text('decisions')->nullable();
            $table->text('commitments')->nullable();
            $table->dateTime('next_meeting_at')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_sleep_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('assessed_at');
            $table->string('usual_habits')->nullable();
            $table->string('subjective_quality')->nullable();
            $table->boolean('nocturnal_pain')->nullable();
            $table->boolean('anxiety')->nullable();
            $table->string('light_exposure')->nullable();
            $table->string('noise_level')->nullable();
            $table->unsignedSmallInteger('interruptions_count')->nullable();
            $table->string('night_medication')->nullable();
            $table->boolean('ventilation_interference')->nullable();
            $table->boolean('procedures_at_night')->nullable();
            $table->string('day_night_orientation')->nullable();
            $table->json('interventions')->nullable();
            $table->text('barriers')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_physical_restraints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->string('restraint_type');
            $table->text('indication')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('reassessed_at')->nullable();
            $table->text('alternatives_tried')->nullable();
            $table->dateTime('removed_at')->nullable();
            $table->text('related_event')->nullable();
            $table->timestamps();
        });

        Schema::create('icu_device_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->string('device_type'); // urinary_catheter | cvc | arterial_line | ngt | drain | other
            $table->dateTime('placed_at')->nullable();
            $table->boolean('still_needed')->nullable();
            $table->dateTime('reviewed_at');
            $table->dateTime('removed_at')->nullable();
            $table->boolean('unplanned_removal')->default(false);
            $table->timestamps();
        });

        Schema::create('icu_transfer_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('cognitive_status_reviewed')->default(false);
            $table->boolean('functional_status_reviewed')->default(false);
            $table->boolean('delirium_reviewed')->default(false);
            $table->boolean('pain_reviewed')->default(false);
            $table->boolean('sedatives_opioids_reviewed')->default(false);
            $table->boolean('withdrawal_risk_reviewed')->default(false);
            $table->boolean('nutrition_reviewed')->default(false);
            $table->boolean('devices_reviewed')->default(false);
            $table->boolean('mobility_rehab_reviewed')->default(false);
            $table->boolean('restraints_reviewed')->default(false);
            $table->boolean('sleep_reviewed')->default(false);
            $table->boolean('emotional_needs_reviewed')->default(false);
            $table->boolean('family_engagement_reviewed')->default(false);
            $table->boolean('pics_risk_reviewed')->default(false);
            $table->boolean('medication_reconciliation')->default(false);
            $table->boolean('continuity_plan_documented')->default(false);
            $table->boolean('warning_signs_documented')->default(false);
            $table->dateTime('handoff_at')->nullable();
            $table->string('team_delivering')->nullable();
            $table->string('team_receiving')->nullable();
            $table->text('information_transmitted')->nullable();
            $table->text('pending_items')->nullable();
            $table->text('risks')->nullable();
            $table->text('rehabilitation_plan')->nullable();
            $table->boolean('understanding_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('icu_pics_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
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
            $table->string('anxiety_tool')->nullable();
            $table->string('anxiety_result')->nullable();
            $table->string('depression_tool')->nullable();
            $table->string('depression_result')->nullable();
            $table->string('ptsd_tool')->nullable();
            $table->string('ptsd_result')->nullable();
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
            $table->timestamps();
        });

        Schema::create('icu_stay_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assessment_finding_id')->nullable()->constrained('assessment_findings')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->json('criteria_met')->nullable();
            $table->json('criteria_not_met')->nullable();
            $table->text('barriers')->nullable();
            $table->text('probable_cause')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('recommendation')->nullable();
            $table->boolean('requires_phva')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'icu_stay_audits', 'icu_pics_followups', 'icu_transfer_checklists', 'icu_device_reviews',
            'icu_physical_restraints', 'icu_sleep_assessments', 'icu_family_engagements', 'icu_mobility_sessions',
            'icu_delirium_assessments', 'icu_sedation_assessments', 'icu_sbt_trials', 'icu_sat_trials',
            'icu_pain_assessments', 'icu_liberation_rounds', 'icu_stays', 'icu_units',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $programId = DB::table('clinical_programs')->where('code', 'ICULIB')->value('id');
        DB::table('clinical_rules')->where('clinical_program_id', $programId)->delete();
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->delete();
        DB::table('clinical_programs')->where('id', $programId)->delete();
    }
};
