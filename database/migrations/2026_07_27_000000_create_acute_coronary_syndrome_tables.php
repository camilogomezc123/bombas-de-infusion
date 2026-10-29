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
            ['code' => 'INFARTO'],
            [
                'name' => 'Programa Institucional de Excelencia en Síndrome Coronario Agudo e Infarto Agudo de Miocardio',
                'short_name' => 'Código Infarto',
                'description' => 'Programa institucional para la atención integral del síndrome coronario agudo.',
                'purpose' => 'Reducir mortalidad, discapacidad, complicaciones, recurrencia y variabilidad clínica mediante reconocimiento temprano, reperfusión, tratamiento basado en evidencia, rehabilitación y prevención secundaria.',
                'target_population' => 'Adultos con sospecha de SCA, STEMI, NSTEMI, NSTE-ACS, angina inestable y complicaciones agudas del infarto.',
                'scope' => 'Desde el inicio de síntomas y primer contacto médico hasta rehabilitación y seguimiento posterior al egreso.',
                'status' => 'implementation',
                'version' => '0.1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        $programId = DB::table('clinical_programs')->where('code', 'INFARTO')->value('id');
        foreach ([
            ['PROC-01', 'ECG interpretado ≤10 minutos', 'ecg_10_pct', 'process', '90'],
            ['STEMI-01', 'FMC-dispositivo ≤90 minutos en ingreso directo', 'fmc_device_direct_90_pct', 'process', '90'],
            ['STEMI-02', 'FMC-dispositivo ≤120 minutos en traslado', 'fmc_device_transfer_120_pct', 'process', '90'],
            ['NSTE-07', 'Angiografía ≤24 horas en estrategia invasiva temprana', 'nste_angio_24_pct', 'process', '90'],
            ['EGR-02', 'Remisión a rehabilitación cardiaca', 'rehab_referral_pct', 'process', '85'],
            ['RES-01', 'Mortalidad hospitalaria', 'hospital_mortality_pct', 'result', null],
            ['RES-06', 'MACE a 30 días', 'mace_30d_pct', 'result', null],
            ['RES-17', 'Reingreso a 30 días', 'readmission_30d_pct', 'result', null],
        ] as $index => [$code, $name, $key, $group, $target]) {
            DB::table('indicator_definitions')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $name, 'indicator_group' => $group, 'unit' => '%',
                    'target_value' => $target, 'expected_direction' => str_starts_with($code, 'RES') ? 'lower_better' : 'higher_better',
                    'source' => 'Registro clínico SCA', 'periodicity' => 'Mensual',
                    'is_core_indicator' => true, 'core_indicator_key' => $key,
                    'sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now(),
                ],
            );
        }

        Schema::create('acs_cases', function (Blueprint $table) {
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
            $table->string('diagnosis')->nullable()->index();
            $table->string('acs_type')->nullable()->index();
            $table->string('infarction_type')->nullable();
            $table->string('entry_route')->nullable()->index();
            $table->string('origin_service')->nullable()->index();
            $table->string('referral_institution')->nullable()->index();
            $table->string('insurer')->nullable();
            $table->string('municipality')->nullable();
            $table->string('presentation_type')->nullable();
            $table->string('killip_class')->nullable();
            $table->text('social_barriers')->nullable();
            $table->json('special_populations')->nullable();
            $table->dateTime('symptom_onset_at')->nullable();
            $table->dateTime('first_medical_contact_at')->nullable();
            $table->dateTime('admission_at')->nullable();
            $table->dateTime('ecg_performed_at')->nullable();
            $table->dateTime('ecg_interpreted_at')->nullable();
            $table->dateTime('diagnosis_at')->nullable();
            $table->dateTime('code_activated_at')->nullable();
            $table->dateTime('cath_lab_activated_at')->nullable();
            $table->dateTime('cath_lab_arrival_at')->nullable();
            $table->dateTime('first_device_at')->nullable();
            $table->dateTime('fibrinolysis_at')->nullable();
            $table->dateTime('angiography_at')->nullable();
            $table->dateTime('discharged_at')->nullable();
            $table->dateTime('death_at')->nullable();
            $table->boolean('cardiac_arrest')->default(false);
            $table->boolean('cardiogenic_shock')->default(false);
            $table->boolean('eligible_for_reperfusion')->nullable();
            $table->string('reperfusion_strategy')->nullable();
            $table->text('no_reperfusion_reason')->nullable();
            $table->string('nste_strategy')->nullable();
            $table->text('conservative_reason')->nullable();
            $table->boolean('icu_admission')->default(false);
            $table->unsignedSmallInteger('icu_stay_days')->nullable();
            $table->unsignedSmallInteger('hospital_stay_days')->nullable();
            $table->string('outcome')->nullable();
            $table->boolean('readmission_30d')->nullable();
            $table->boolean('reinfarction_30d')->nullable();
            $table->boolean('mace_30d')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_cancelled')->default(false);
            $table->text('exclusion_reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['acs_type', 'admission_at']);
            $table->index(['site_id', 'admission_at']);
        });

        Schema::create('acs_ecg_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('performed_at');
            $table->dateTime('interpreted_at')->nullable();
            $table->string('result')->nullable();
            $table->boolean('st_elevation')->nullable();
            $table->boolean('transmitted_prehospital')->nullable();
            $table->text('interpretation')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_troponin_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('collected_at');
            $table->dateTime('resulted_at')->nullable();
            $table->decimal('value', 14, 4)->nullable();
            $table->string('unit')->nullable();
            $table->boolean('high_sensitivity')->default(true);
            $table->string('interpretation')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_pci_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('first_device_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('operator')->nullable();
            $table->string('vascular_access')->nullable();
            $table->string('culprit_artery')->nullable();
            $table->string('initial_flow')->nullable();
            $table->string('final_flow')->nullable();
            $table->boolean('multivessel_disease')->nullable();
            $table->boolean('intracoronary_imaging')->nullable();
            $table->string('intervention_type')->nullable();
            $table->boolean('successful')->nullable();
            $table->text('complications')->nullable();
            $table->text('complete_revascularization_plan')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_medication_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->string('medication_group');
            $table->string('medication_name')->nullable();
            $table->dateTime('ordered_at')->nullable();
            $table->dateTime('administered_at')->nullable();
            $table->string('documented_dose')->nullable();
            $table->boolean('indicated')->nullable();
            $table->boolean('at_discharge')->nullable();
            $table->text('contraindication_or_omission_reason')->nullable();
            $table->text('discharge_plan')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_complications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->dateTime('recognized_at')->nullable();
            $table->string('severity')->nullable();
            $table->text('management')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_discharge_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('final_diagnosis')->nullable();
            $table->text('coronary_anatomy')->nullable();
            $table->decimal('ejection_fraction', 5, 2)->nullable();
            $table->boolean('medication_reconciliation')->default(false);
            $table->boolean('dapt_plan')->default(false);
            $table->boolean('high_intensity_statin')->default(false);
            $table->boolean('verbal_education')->default(false);
            $table->boolean('written_education')->default(false);
            $table->boolean('teach_back')->default(false);
            $table->boolean('warning_signs')->default(false);
            $table->boolean('smoking_plan')->default(false);
            $table->boolean('cardiology_appointment')->default(false);
            $table->boolean('primary_care_appointment')->default(false);
            $table->boolean('rehabilitation_referral')->default(false);
            $table->boolean('lipid_profile_plan')->default(false);
            $table->boolean('medication_access_verified')->default(false);
            $table->text('barriers_and_plan')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_rehabilitation_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->date('referred_on')->nullable();
            $table->date('contacted_on')->nullable();
            $table->date('started_on')->nullable();
            $table->string('modality')->nullable();
            $table->unsignedSmallInteger('sessions_planned')->nullable();
            $table->unsignedSmallInteger('sessions_completed')->nullable();
            $table->string('status')->default('referred');
            $table->text('barriers')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_on');
            $table->dateTime('contacted_at')->nullable();
            $table->string('milestone');
            $table->boolean('contact_achieved')->default(false);
            $table->boolean('bleeding')->nullable();
            $table->boolean('readmission')->nullable();
            $table->boolean('reinfarction')->nullable();
            $table->boolean('rehabilitation_started')->nullable();
            $table->boolean('mortality')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('adherence_and_access')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('acs_case_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acs_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority_reason')->nullable();
            $table->string('status')->default('pending');
            $table->json('criteria_met')->nullable();
            $table->json('criteria_not_met')->nullable();
            $table->text('barriers')->nullable();
            $table->text('probable_cause')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('recommendation')->nullable();
            $table->boolean('requires_five_whys')->default(false);
            $table->boolean('requires_phva')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acs_case_audits');
        Schema::dropIfExists('acs_followups');
        Schema::dropIfExists('acs_rehabilitation_referrals');
        Schema::dropIfExists('acs_discharge_plans');
        Schema::dropIfExists('acs_complications');
        Schema::dropIfExists('acs_medication_records');
        Schema::dropIfExists('acs_pci_procedures');
        Schema::dropIfExists('acs_troponin_records');
        Schema::dropIfExists('acs_ecg_records');
        Schema::dropIfExists('acs_cases');
        $programId = DB::table('clinical_programs')->where('code', 'INFARTO')->value('id');
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->delete();
        DB::table('clinical_programs')->where('id', $programId)->delete();
    }
};
