<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_hemodynamic_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('assessed_at')->index();
            $table->decimal('map', 6, 2)->nullable();
            $table->decimal('diastolic_pressure', 6, 2)->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->decimal('capillary_refill_seconds', 5, 2)->nullable();
            $table->decimal('lactate', 6, 2)->nullable();
            $table->decimal('urine_output_ml', 8, 2)->nullable();
            $table->string('mental_status')->nullable();
            $table->boolean('cold_mottled_skin')->nullable();
            $table->boolean('congestion')->nullable();
            $table->string('volume_response_method')->nullable();
            $table->string('volume_response_result')->nullable();
            $table->string('phenotype', 60)->nullable()->index();
            $table->text('ultrasound_findings')->nullable();
            $table->decimal('vti', 6, 2)->nullable();
            $table->string('lv_function')->nullable();
            $table->string('rv_function')->nullable();
            $table->decimal('cvp', 6, 2)->nullable();
            $table->text('vasopressors')->nullable();
            $table->text('inotropes')->nullable();
            $table->text('conduct')->nullable();
            $table->dateTime('next_assessment_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_hemodynamic_assessments');
    }
};
