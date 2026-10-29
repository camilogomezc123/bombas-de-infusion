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
            ['code' => 'TEP'],
            [
                'name' => 'Programa Institucional de Excelencia en Tromboembolismo Pulmonar Agudo y Seguimiento Post-TEP',
                'short_name' => 'Código TEP',
                'description' => 'Programa institucional para la atención integral del tromboembolismo pulmonar.',
                'purpose' => 'Mejorar el reconocimiento, diagnóstico, estratificación documentada, tratamiento, transición y seguimiento post-TEP.',
                'target_population' => 'Adultos con sospecha, confirmación o hallazgo incidental de tromboembolismo pulmonar.',
                'scope' => 'Desde la sospecha clínica y activación PERT hasta el seguimiento post-TEP y la evaluación de enfermedad tromboembólica pulmonar crónica.',
                'status' => 'implementation',
                'version' => '0.1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $programId = DB::table('clinical_programs')->where('code', 'TEP')->value('id');
        foreach ([
            ['DX-01', 'Probabilidad pretest documentada', 'pretest_documented_pct', 'process', '90'],
            ['RISK-01', 'Categoría A-E documentada', 'category_documented_pct', 'process', '90'],
            ['PERT-01', 'Activación PERT en categorías C-E', 'pert_activation_pct', 'process', '90'],
            ['TX-01', 'Anticoagulación documentada o contraindicación', 'anticoagulation_documented_pct', 'process', '95'],
            ['SEG-01', 'Seguimiento temprano post-TEP', 'early_followup_pct', 'process', '85'],
            ['SEG-02', 'Seguimiento a tres meses', 'followup_3m_pct', 'process', '80'],
            ['RES-01', 'Mortalidad hospitalaria', 'hospital_mortality_pct', 'result', null],
            ['SEG-03', 'Síntomas persistentes o evaluación CTEPD', 'persistent_symptoms_pct', 'result', null],
        ] as $index => [$code, $name, $key, $group, $target]) {
            DB::table('indicator_definitions')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $name,
                    'indicator_group' => $group,
                    'unit' => '%',
                    'target_value' => $target,
                    'expected_direction' => str_starts_with($code, 'RES') || $code === 'SEG-03' ? 'lower_better' : 'higher_better',
                    'source' => 'Registro clínico TEP',
                    'periodicity' => 'Mensual',
                    'is_core_indicator' => true,
                    'core_indicator_key' => $key,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        foreach ([
            ['TEP-DX-01', 'Documentar probabilidad clínica pretest antes de interpretar la ruta diagnóstica.'],
            ['TEP-RISK-01', 'Registrar la categoría A-E determinada por el equipo clínico y su justificación.'],
            ['TEP-PERT-01', 'Documentar activación o razón de no activación PERT en categorías C-E.'],
            ['TEP-TX-01', 'Documentar anticoagulación, tiempos o contraindicación clínica.'],
            ['TEP-SEG-01', 'Definir seguimiento temprano y reevaluación alrededor de tres meses.'],
        ] as [$code, $title]) {
            DB::table('clinical_rules')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $title,
                    'description' => 'Regla institucional en borrador; requiere aprobación y validación contra la guía fuente.',
                    'version' => '0.1',
                    'status' => 'draft',
                    'source_document' => 'Guía AHA/ACC 2026 pendiente de incorporar al repositorio',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::create('tep_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('case_number')->unique();
            $table->unsignedBigInteger('case_sequence')->unique();
            $table->string('admission_number')->nullable()->index();
            $table->string('status')->default('registered')->index();
            $table->string('presentation')->nullable();
            $table->string('entry_route')->nullable()->index();
            $table->string('origin_service')->nullable();
            $table->boolean('tep_suspected')->default(true);
            $table->boolean('tep_confirmed')->nullable()->index();
            $table->boolean('incidental_tep')->default(false);
            $table->dateTime('symptom_onset_at')->nullable();
            $table->dateTime('admission_at')->nullable()->index();
            $table->dateTime('diagnosis_at')->nullable();
            $table->string('pretest_method')->nullable();
            $table->string('pretest_result')->nullable();
            $table->decimal('pretest_score', 8, 2)->nullable();
            $table->boolean('d_dimer_indicated')->nullable();
            $table->decimal('d_dimer_value', 12, 2)->nullable();
            $table->string('d_dimer_unit')->nullable();
            $table->string('diagnostic_imaging')->nullable();
            $table->string('imaging_result')->nullable();
            $table->dateTime('imaging_ordered_at')->nullable();
            $table->dateTime('imaging_completed_at')->nullable();
            $table->string('aha_category')->nullable()->index();
            $table->string('aha_subcategory')->nullable();
            $table->dateTime('category_documented_at')->nullable();
            $table->text('category_justification')->nullable();
            $table->unsignedSmallInteger('pesi_score')->nullable();
            $table->boolean('spesi_high_risk')->nullable();
            $table->boolean('hestia_positive')->nullable();
            $table->boolean('rv_dysfunction')->nullable();
            $table->boolean('troponin_positive')->nullable();
            $table->boolean('hypotension')->nullable();
            $table->boolean('shock')->default(false);
            $table->boolean('cardiac_arrest')->default(false);
            $table->boolean('pert_required')->nullable();
            $table->dateTime('pert_activated_at')->nullable();
            $table->text('pert_not_activated_reason')->nullable();
            $table->dateTime('anticoagulation_ordered_at')->nullable();
            $table->dateTime('anticoagulation_started_at')->nullable();
            $table->string('anticoagulation_type')->nullable();
            $table->text('anticoagulation_contraindication')->nullable();
            $table->string('disposition')->nullable();
            $table->boolean('icu_admission')->default(false);
            $table->unsignedSmallInteger('icu_stay_days')->nullable();
            $table->unsignedSmallInteger('hospital_stay_days')->nullable();
            $table->dateTime('discharged_at')->nullable();
            $table->dateTime('death_at')->nullable();
            $table->boolean('major_bleeding')->nullable();
            $table->boolean('recurrence_30d')->nullable();
            $table->boolean('recurrence_90d')->nullable();
            $table->boolean('readmission_30d')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_cancelled')->default(false);
            $table->text('exclusion_reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tep_pert_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('decision_at')->nullable();
            $table->json('participants')->nullable();
            $table->string('decision')->nullable();
            $table->text('rationale')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_imaging_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->string('study_type');
            $table->dateTime('ordered_at')->nullable();
            $table->dateTime('performed_at')->nullable();
            $table->string('result')->nullable();
            $table->text('findings')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_anticoagulation_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->string('medication')->nullable();
            $table->dateTime('ordered_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->string('phase')->nullable();
            $table->string('documented_dose')->nullable();
            $table->text('contraindication_or_omission_reason')->nullable();
            $table->text('transition_plan')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_advanced_therapies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->string('therapy_type');
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->boolean('completed')->nullable();
            $table->text('indication')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_ivc_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('placed_at')->nullable();
            $table->text('indication')->nullable();
            $table->date('retrieval_planned_on')->nullable();
            $table->date('retrieved_on')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_transfer_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->string('direction')->nullable();
            $table->string('institution')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_discharge_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('medication_reconciliation')->default(false);
            $table->boolean('anticoagulation_plan')->default(false);
            $table->boolean('duration_documented')->default(false);
            $table->boolean('bleeding_education')->default(false);
            $table->boolean('warning_signs')->default(false);
            $table->boolean('written_education')->default(false);
            $table->boolean('followup_scheduled')->default(false);
            $table->date('first_followup_on')->nullable();
            $table->text('barriers_and_plan')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->string('milestone');
            $table->date('scheduled_on')->nullable();
            $table->dateTime('contacted_at')->nullable();
            $table->boolean('contact_achieved')->default(false);
            $table->boolean('persistent_dyspnea')->nullable();
            $table->boolean('bleeding')->nullable();
            $table->boolean('recurrence')->nullable();
            $table->boolean('readmission')->nullable();
            $table->boolean('mortality')->nullable();
            $table->text('adherence_and_access')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_ctepd_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->date('evaluated_on')->nullable();
            $table->boolean('persistent_symptoms')->nullable();
            $table->string('study')->nullable();
            $table->string('result')->nullable();
            $table->boolean('ctepd_suspected')->nullable();
            $table->boolean('cteph_suspected')->nullable();
            $table->text('referral_plan')->nullable();
            $table->timestamps();
        });
        Schema::create('tep_case_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tep_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
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
        foreach (['tep_case_audits', 'tep_ctepd_evaluations', 'tep_followups', 'tep_discharge_plans', 'tep_transfer_records', 'tep_ivc_filters', 'tep_advanced_therapies', 'tep_anticoagulation_episodes', 'tep_imaging_studies', 'tep_pert_activations', 'tep_cases'] as $table) {
            Schema::dropIfExists($table);
        }
        $programId = DB::table('clinical_programs')->where('code', 'TEP')->value('id');
        DB::table('clinical_rules')->where('clinical_program_id', $programId)->delete();
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->delete();
        DB::table('clinical_programs')->where('id', $programId)->delete();
    }
};
