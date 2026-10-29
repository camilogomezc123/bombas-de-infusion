<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drug_infusion_profiles', function (Blueprint $table) {
            // Umbral institucional de dosis acumulada por kilogramo por día (ej. mg/kg/día),
            // independiente del umbral de tasa (max_dose_soft/hard). Nulo = sin umbral definido.
            $table->decimal('max_cumulative_dose_per_kg_per_day', 10, 4)->nullable()->after('dose_limit_unit');
        });
    }

    public function down(): void
    {
        Schema::table('drug_infusion_profiles', function (Blueprint $table) {
            $table->dropColumn('max_cumulative_dose_per_kg_per_day');
        });
    }
};
