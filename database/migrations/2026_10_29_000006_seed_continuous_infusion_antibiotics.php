<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de antibióticos en infusión continua/extendida usados en el protocolo de
 * código sepsis de la institución. Umbrales de interrupción (max_interruption_minutes)
 * son un punto de partida clínico razonable, no una guía farmacológica — ajustar por
 * farmacia según el antibiótico y la estabilidad de la dilución.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['Piperacilina/Tazobactam', ['piperacilina', 'piperacilina tazobactam', 'piperacilina/tazobactam', 'tazonam', 'pip-tazo', 'piptazo'], 45],
            ['Meropenem', ['meropenem'], 30],
            ['Vancomicina', ['vancomicina', 'vancomycin'], 60],
        ] as [$name, $aliases, $maxInterruptionMinutes]) {
            DB::table('drug_infusion_profiles')->updateOrInsert(
                ['name' => $name],
                [
                    'aliases' => json_encode($aliases),
                    'category' => 'antibiotic',
                    'dose_unit' => 'mg/h',
                    'continuous_infusion' => true,
                    'max_interruption_minutes' => $maxInterruptionMinutes,
                    'vis_component' => false,
                    'pis_risk_applicable' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('drug_infusion_profiles')->whereIn('name', ['Piperacilina/Tazobactam', 'Meropenem', 'Vancomicina'])->delete();
    }
};
