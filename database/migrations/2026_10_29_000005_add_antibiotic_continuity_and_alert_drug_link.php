<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporta antibióticos en infusión continua/extendida (ej. piperacilina-tazobactam,
 * meropenem, vancomicina) como las que se programan en código sepsis: a diferencia de
 * los vasoactivos/sedación, lo clínicamente crítico aquí no es la dosis máxima sino que
 * la infusión NO se interrumpa (subdosificación = niveles subterapéuticos). También
 * enlaza cada alerta clínica al fármaco que la generó, para poder mostrarla junto al
 * canal correspondiente en la Central de Monitoreo en vez de solo como un contador
 * agregado por paciente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drug_infusion_profiles', function (Blueprint $table) {
            $table->boolean('continuous_infusion')->default(false)->after('category');
            $table->unsignedInteger('max_interruption_minutes')->nullable()->after('continuous_infusion');
        });

        Schema::table('infusion_clinical_alerts', function (Blueprint $table) {
            $table->foreignId('drug_infusion_profile_id')->nullable()->after('infusion_event_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('infusion_clinical_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drug_infusion_profile_id');
        });

        Schema::table('drug_infusion_profiles', function (Blueprint $table) {
            $table->dropColumn(['continuous_infusion', 'max_interruption_minutes']);
        });
    }
};
