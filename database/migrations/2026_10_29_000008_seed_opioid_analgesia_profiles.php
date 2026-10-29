<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de opioides en infusión continua (analgesia), sin peso VIS (el VIS solo
 * pondera vasoactivos) y sin el flag continuous_infusion de antibióticos (una
 * interrupción de opioide se titula clínicamente, no es un riesgo de subdosificación
 * como en código sepsis). Umbrales institucionales de referencia, no guía farmacológica.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['Fentanilo', ['fentanilo', 'fentanyl'], 'mcg/kg/h', 2, 5],
            ['Remifentanilo', ['remifentanilo', 'remifentanil'], 'mcg/kg/min', 0.15, 0.5],
            ['Morfina', ['morfina', 'morphine'], 'mg/kg/h', 0.05, 0.1],
            ['Hidromorfona', ['hidromorfona', 'hydromorphone'], 'mg/kg/h', 0.01, 0.02],
        ] as [$name, $aliases, $unit, $soft, $hard]) {
            DB::table('drug_infusion_profiles')->updateOrInsert(
                ['name' => $name],
                [
                    'aliases' => json_encode($aliases),
                    'category' => 'analgesia',
                    'dose_unit' => $unit,
                    'vis_component' => false,
                    'max_dose_soft' => $soft,
                    'max_dose_hard' => $hard,
                    'dose_limit_unit' => $unit,
                    'continuous_infusion' => false,
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
        DB::table('drug_infusion_profiles')->whereIn('name', ['Fentanilo', 'Remifentanilo', 'Morfina', 'Hidromorfona'])->delete();
    }
};
