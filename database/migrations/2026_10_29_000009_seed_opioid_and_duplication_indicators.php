<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'BOMBAS')->value('id');

        if (! $programId) {
            return;
        }

        foreach ([
            ['BOMBAS-07', 'Infusión de opioide sin dolor evaluado (ABCDEF)', 'open_pain_assessment_gaps', 'alertas', 'lower_better',
                'Alertas abiertas de infusión activa de opioide sin evaluación de dolor registrada en ICU Liberation dentro de la ventana de vigilancia — cruza el dato estructurado de bomba con el componente "A" del bundle ABCDEF.'],
            ['BOMBAS-08', 'Duplicidad terapéutica (analgesia/sedación)', 'open_therapeutic_duplications', 'alertas', 'lower_better',
                'Alertas abiertas por más de un analgésico opioide o más de un sedante activos a la vez para la misma estancia — posible duplicidad de prescripción.'],
        ] as $index => [$code, $name, $key, $unit, $direction, $observations]) {
            DB::table('indicator_definitions')->updateOrInsert(
                ['clinical_program_id' => $programId, 'code' => $code],
                [
                    'name' => $name,
                    'indicator_group' => 'process',
                    'unit' => $unit,
                    'expected_direction' => $direction,
                    'source' => 'Módulo Monitoreo de Bombas (InfusionIndicatorService)',
                    'observations' => $observations,
                    'periodicity' => 'Tiempo real',
                    'is_core_indicator' => true,
                    'core_indicator_key' => $key,
                    'sort_order' => 7 + $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'BOMBAS')->value('id');
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->whereIn('code', ['BOMBAS-07', 'BOMBAS-08'])->delete();
    }
};
