<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->dateTime('occurred_at')->index();
            $table->string('service')->nullable();
            $table->string('clinician_name')->nullable();
            $table->boolean('suspected_infection')->nullable();
            $table->string('probable_focus')->nullable();
            $table->unsignedTinyInteger('news2_score')->nullable();
            $table->unsignedTinyInteger('sofa_score')->nullable();
            $table->string('mental_status')->nullable();
            $table->decimal('urine_output_ml', 8, 2)->nullable();
            $table->string('perfusion_status')->nullable();
            $table->json('vital_signs')->nullable();
            $table->string('result')->nullable();
            $table->text('conduct')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_screenings');
    }
};
