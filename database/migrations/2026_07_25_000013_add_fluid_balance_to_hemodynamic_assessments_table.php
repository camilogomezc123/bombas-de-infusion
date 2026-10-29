<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_hemodynamic_assessments', function (Blueprint $table) {
            $table->decimal('fluid_intake_ml', 8, 1)->nullable()->after('urine_output_ml');
            $table->decimal('fluid_output_ml', 8, 1)->nullable()->after('fluid_intake_ml');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_hemodynamic_assessments', function (Blueprint $table) {
            $table->dropColumn(['fluid_intake_ml', 'fluid_output_ml']);
        });
    }
};
