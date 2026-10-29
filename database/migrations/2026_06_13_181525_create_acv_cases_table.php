<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acv_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('assigned_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('case_number')->unique();
            $table->unsignedInteger('case_sequence')->unique();
            $table->string('admission_number')->nullable()->index();
            $table->string('resq_code')->nullable()->index();
            $table->string('status')->default('imported')->index();
            $table->string('month', 7)->nullable()->index();
            $table->string('eapb')->nullable()->index();
            $table->string('health_regime')->nullable();
            $table->string('stroke_type')->nullable()->index();
            $table->dateTime('arrival_at')->nullable()->index();
            $table->dateTime('last_known_well_at')->nullable();
            $table->dateTime('imaging_at')->nullable();
            $table->dateTime('thrombolysis_at')->nullable();
            $table->dateTime('groin_puncture_at')->nullable();
            $table->dateTime('revascularization_at')->nullable();
            $table->dateTime('speech_therapy_at')->nullable();
            $table->dateTime('physiotherapy_at')->nullable();
            $table->dateTime('discharged_at')->nullable();
            $table->boolean('thrombolysed')->nullable()->index();
            $table->boolean('thrombectomy')->nullable()->index();
            $table->boolean('deceased')->nullable()->index();
            $table->boolean('hemorrhagic_transformation')->nullable();
            $table->boolean('is_recurrence')->default(false)->index();
            $table->boolean('is_cancelled')->default(false)->index();
            $table->text('cancellation_reason')->nullable();
            $table->json('clinical_data')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('analysis_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_cancelled']);
            $table->index(['month', 'stroke_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acv_cases');
    }
};
