<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_patient_education_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('informed_person');
            $table->string('relationship_to_patient')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->boolean('diagnosis_explained')->default(false);
            $table->boolean('treatment_explained')->default(false);
            $table->boolean('risks_explained')->default(false);
            $table->boolean('procedures_explained')->default(false);
            $table->boolean('prognosis_explained')->default(false);
            $table->text('goals_of_care')->nullable();
            $table->text('preferences')->nullable();
            $table->text('comprehension_barriers')->nullable();
            $table->string('material_provided')->nullable();
            $table->boolean('teach_back_done')->default(false);
            $table->boolean('discharge_readiness')->nullable();
            $table->text('warning_signs')->nullable();
            $table->text('post_sepsis_follow_up')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_patient_education_records');
    }
};
