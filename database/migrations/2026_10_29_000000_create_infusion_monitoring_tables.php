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
            ['code' => 'BOMBAS'],
            [
                'name' => 'Programa de Monitoreo Clínico de Bombas de Infusión',
                'short_name' => 'Monitoreo de Bombas',
                'description' => 'Captura los eventos reportados por las bombas de infusión inteligentes (DERS, límites blandos/duros, anulaciones y retrocesos) y los correlaciona con el paciente para apoyar decisiones clínicas: score VIS, dosis acumulada y riesgo de síndrome de infusión por propofol.',
                'purpose' => 'Reducir el tiempo de detección de excedentes de dosis y aportar trazabilidad objetiva de la terapia vasoactiva y de sedación infundida por bomba.',
                'target_population' => 'Pacientes con estancia registrada en unidades de cuidado crítico con bombas de infusión monitoreadas.',
                'scope' => 'Desde la captura del evento de la bomba hasta su correlación con la estancia del paciente y el cálculo de los indicadores clínicos derivados.',
                'status' => 'implementation',
                'version' => '0.1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Schema::create('pump_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('icu_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hospital_device_id')->unique(); // "ID DEL DISPOSITIVO HOSPITALARIO"
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable(); // ej. "EVO LVP"
            $table->string('device_group')->nullable(); // ej. "Adultos"
            $table->string('current_bed_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('drug_infusion_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('aliases')->nullable(); // variantes del nombre tal como lo reporta la bomba
            $table->string('category')->default('other'); // vasoactive | sedation | analgesia | antiemetic | other
            $table->string('dose_unit')->nullable(); // unidad de referencia institucional, ej. mcg/kg/min
            $table->boolean('vis_component')->default(false);
            $table->decimal('vis_weight', 10, 4)->nullable(); // factor del score VIS (ver VisScoreCalculator)
            $table->string('vis_rate_unit')->nullable(); // mcg/kg/min | units/kg/min
            $table->decimal('max_dose_soft', 10, 4)->nullable();
            $table->decimal('max_dose_hard', 10, 4)->nullable();
            $table->string('dose_limit_unit')->nullable();
            $table->boolean('pis_risk_applicable')->default(false);
            $table->decimal('pis_dose_threshold', 10, 4)->nullable(); // mg/kg/h
            $table->unsignedInteger('pis_duration_threshold_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('icu_stay_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 6, 2);
            $table->dateTime('recorded_at');
            $table->string('source')->default('manual'); // manual | admission
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('infusion_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pump_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('icu_stay_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('drug_infusion_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_infusion_id')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->string('medication_name_raw');
            $table->string('concentration_raw')->nullable();
            $table->string('limit_type')->nullable(); // ej. "Tasa de dosis"
            $table->string('alert_level')->nullable(); // DERS - Blando | DERS - Duro | PUMP - Duro | null
            $table->string('next_action')->nullable(); // Anulaciones | Retrocesos | null
            $table->string('care_area')->nullable(); // área/cubículo reportado por la bomba
            $table->decimal('attempted_value', 12, 4)->nullable();
            $table->string('attempted_unit')->nullable();
            $table->decimal('soft_limit_low', 12, 4)->nullable();
            $table->decimal('soft_limit_high', 12, 4)->nullable();
            $table->decimal('hard_limit_low', 12, 4)->nullable();
            $table->decimal('hard_limit_high', 12, 4)->nullable();
            $table->decimal('existing_dose_rate_value', 12, 4)->nullable();
            $table->string('existing_dose_rate_unit')->nullable();
            $table->string('next_drug')->nullable();
            $table->json('raw_payload')->nullable();
            $table->dateTime('ingested_at');
            $table->timestamps();
            $table->unique(['vendor_infusion_id', 'occurred_at'], 'infusion_events_vendor_occurred_unique');
        });

        Schema::create('infusion_dose_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_infusion_profile_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            $table->decimal('cumulative_dose', 14, 4)->default(0);
            $table->string('dose_unit')->nullable();
            $table->decimal('max_rate_seen', 12, 4)->nullable();
            $table->string('max_rate_unit')->nullable();
            $table->dateTime('computed_at');
            $table->timestamps();
            $table->unique(['icu_stay_id', 'drug_infusion_profile_id', 'summary_date'], 'infusion_dose_summaries_unique');
        });

        Schema::create('vis_score_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->dateTime('computed_at')->index();
            $table->decimal('vis_score', 10, 2);
            $table->json('breakdown')->nullable(); // aporte por fármaco
            $table->decimal('weight_kg_used', 6, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('infusion_clinical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_stay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('infusion_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // hard_limit_override | max_dose_exceeded | pis_risk | vis_critical
            $table->string('severity')->default('warning'); // info | warning | critical
            $table->text('message');
            $table->string('status')->default('open')->index(); // open | acknowledged | resolved
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Catálogo clínico inicial. Pesos VIS estándar (Gaies et al.) en mcg/kg/min, salvo
        // vasopresina en U/kg/min; propofol marcado con umbral de riesgo PIS por defecto
        // (4 mg/kg/h sostenido por 48h) — ver PisRiskEvaluator para el alcance real del cálculo.
        foreach ([
            ['Dopamina', ['dopamina', 'dopamine'], 'vasoactive', true, 1, 'mcg/kg/min', 20, 50, 'mcg/kg/min', false, null, null],
            ['Dobutamina', ['dobutamina', 'dobutamine'], 'vasoactive', true, 1, 'mcg/kg/min', 20, 40, 'mcg/kg/min', false, null, null],
            ['Epinefrina', ['epinefrina', 'adrenalina', 'epinephrine'], 'vasoactive', true, 100, 'mcg/kg/min', 0.5, 1, 'mcg/kg/min', false, null, null],
            ['Norepinefrina', ['norepinefrina', 'noradrenalina', 'norepinephrine'], 'vasoactive', true, 100, 'mcg/kg/min', 1, 3.3, 'mcg/kg/min', false, null, null],
            ['Milrinona', ['milrinona', 'milrinone'], 'vasoactive', true, 10, 'mcg/kg/min', 0.75, 1, 'mcg/kg/min', false, null, null],
            ['Vasopresina', ['vasopresina', 'vasopressin'], 'vasoactive', true, 10000, 'units/kg/min', 0.04, 0.1, 'units/kg/min', false, null, null],
            ['Propofol', ['propofol'], 'sedation', false, null, null, 4, 5, 'mg/kg/h', true, 4, 2880],
        ] as [$name, $aliases, $category, $visComponent, $visWeight, $visUnit, $maxSoft, $maxHard, $limitUnit, $pisApplicable, $pisThreshold, $pisMinutes]) {
            DB::table('drug_infusion_profiles')->updateOrInsert(
                ['name' => $name],
                [
                    'aliases' => json_encode($aliases),
                    'category' => $category,
                    'dose_unit' => $limitUnit,
                    'vis_component' => $visComponent,
                    'vis_weight' => $visWeight,
                    'vis_rate_unit' => $visUnit,
                    'max_dose_soft' => $maxSoft,
                    'max_dose_hard' => $maxHard,
                    'dose_limit_unit' => $limitUnit,
                    'pis_risk_applicable' => $pisApplicable,
                    'pis_dose_threshold' => $pisThreshold,
                    'pis_duration_threshold_minutes' => $pisMinutes,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        foreach ([
            'infusion_clinical_alerts', 'vis_score_snapshots', 'infusion_dose_summaries',
            'infusion_events', 'icu_stay_weights', 'drug_infusion_profiles', 'pump_devices',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        DB::table('clinical_programs')->where('code', 'BOMBAS')->delete();
    }
};
