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

        DB::table('indicator_definitions')->updateOrInsert(
            ['clinical_program_id' => $programId, 'code' => 'BOMBAS-09'],
            [
                'name' => 'Doble verificación de fármacos de alto riesgo',
                'indicator_group' => 'process',
                'unit' => '%',
                'expected_direction' => 'higher_better',
                'source' => 'Módulo Monitoreo de Bombas (InfusionIndicatorService)',
                'observations' => 'Porcentaje de eventos de vasoactivos/opioides/sedación con doble verificación (co-firma) registrada por un segundo verificador, independiente de quién programó la bomba.',
                'periodicity' => 'Tiempo real',
                'is_core_indicator' => true,
                'core_indicator_key' => 'co_sign_compliance_pct',
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'BOMBAS')->value('id');
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->where('code', 'BOMBAS-09')->delete();
    }
};
