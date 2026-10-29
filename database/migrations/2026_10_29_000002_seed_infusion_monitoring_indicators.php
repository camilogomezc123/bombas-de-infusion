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

        $indicators = [
            ['BOMBAS-01', 'Anulación/retroceso de límites duros', 'hard_limit_override_rate_pct', '%', 'lower_better',
                'Porcentaje de eventos de bomba con límite duro anulado o retrocedido sobre el total de eventos registrados.'],
            ['BOMBAS-02', 'Score VIS promedio (estancias activas)', 'avg_vis_score', 'puntos', null,
                'Promedio del último score VIS calculado por estancia activa con peso registrado. No tiene meta institucional fija — depende de la gravedad de la población atendida.'],
            ['BOMBAS-03', 'Alertas de riesgo PIS abiertas', 'open_pis_alerts', 'alertas', 'lower_better',
                'Número de alertas de tamizaje de riesgo de síndrome de infusión por propofol sin resolver.'],
            ['BOMBAS-04', 'Eventos sin paciente asociado', 'unresolved_events_pct', '%', 'lower_better',
                'Porcentaje de eventos de bomba que no se pudieron enlazar a una estancia activa — indica mapeo bomba↔cama desactualizado.'],
        ];

        foreach ($indicators as $index => [$code, $name, $key, $unit, $direction, $observations]) {
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
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'BOMBAS')->value('id');
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)
            ->whereIn('code', ['BOMBAS-01', 'BOMBAS-02', 'BOMBAS-03', 'BOMBAS-04'])
            ->delete();
    }
};
