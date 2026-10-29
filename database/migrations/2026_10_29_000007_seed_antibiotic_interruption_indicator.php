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
            ['clinical_program_id' => $programId, 'code' => 'BOMBAS-06'],
            [
                'name' => 'Interrupciones de antibiótico en infusión continua',
                'indicator_group' => 'process',
                'unit' => 'alertas',
                'expected_direction' => 'lower_better',
                'source' => 'Módulo Monitoreo de Bombas (InfusionIndicatorService)',
                'observations' => 'Alertas abiertas de posible interrupción de antibióticos en infusión continua/extendida (piperacilina-tazobactam, meropenem, vancomicina) programados en código sepsis — una interrupción prolongada deja niveles subterapéuticos del antimicrobiano.',
                'periodicity' => 'Tiempo real',
                'is_core_indicator' => true,
                'core_indicator_key' => 'open_antibiotic_interruptions',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'BOMBAS')->value('id');
        DB::table('indicator_definitions')->where('clinical_program_id', $programId)->where('code', 'BOMBAS-06')->delete();
    }
};
