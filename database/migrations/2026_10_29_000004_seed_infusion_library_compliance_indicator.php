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
            ['clinical_program_id' => $programId, 'code' => 'BOMBAS-05'],
            [
                'name' => 'Cumplimiento de librería de dosis',
                'indicator_group' => 'process',
                'unit' => '%',
                'expected_direction' => 'higher_better',
                'source' => 'Módulo Monitoreo de Bombas (InfusionIndicatorService)',
                'observations' => 'Porcentaje de eventos de bomba programados usando la librería de dosis segura (DERS) en vez de en modo libre — métrica estándar de gobierno de bombas inteligentes (equivalente al "drug library compliance" de Fresenius/B.Braun/BD).',
                'periodicity' => 'Tiempo real',
                'is_core_indicator' => true,
                'core_indicator_key' => 'library_compliance_pct',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'BOMBAS')->value('id');
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->where('code', 'BOMBAS-05')->delete();
    }
};
