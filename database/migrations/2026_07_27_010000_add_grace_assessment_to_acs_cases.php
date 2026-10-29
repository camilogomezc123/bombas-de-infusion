<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acs_cases', function (Blueprint $table) {
            $table->unsignedSmallInteger('grace_score')->nullable()->after('killip_class');
            $table->string('grace_risk_category')->nullable()->after('grace_score')->index();
            $table->dateTime('grace_assessed_at')->nullable()->after('grace_risk_category');
        });

        $programId = DB::table('clinical_programs')->where('code', 'INFARTO')->value('id');
        DB::table('indicator_definitions')->updateOrInsert(
            ['clinical_program_id' => $programId, 'code' => 'NSTE-04'],
            [
                'name' => 'Riesgo GRACE medido y documentado',
                'indicator_group' => 'process',
                'operational_definition' => 'Proporción de casos NSTE-ACS/NSTEMI con puntaje, categoría y fecha de valoración GRACE documentados.',
                'numerator' => 'Casos elegibles con GRACE completo.',
                'denominator' => 'Casos válidos NSTE-ACS, NSTEMI o angina inestable.',
                'formula' => 'Numerador / denominador × 100.',
                'unit' => '%',
                'target_value' => '90',
                'expected_direction' => 'higher_better',
                'source' => 'Registro clínico SCA',
                'periodicity' => 'Mensual',
                'is_core_indicator' => true,
                'core_indicator_key' => 'grace_documented_pct',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'INFARTO')->value('id');
        DB::table('indicator_definitions')
            ->where('clinical_program_id', $programId)
            ->where('code', 'NSTE-04')
            ->delete();

        Schema::table('acs_cases', function (Blueprint $table) {
            $table->dropIndex(['grace_risk_category']);
            $table->dropColumn(['grace_score', 'grace_risk_category', 'grace_assessed_at']);
        });
    }
};
