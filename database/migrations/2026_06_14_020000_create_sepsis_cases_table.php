<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('assigned_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('case_number')->unique();
            $table->unsignedInteger('case_sequence')->unique();
            $table->string('admission_number')->nullable()->index();
            $table->string('status')->default('completed')->index();
            $table->string('month', 7)->nullable()->index();
            $table->date('registered_on')->nullable();

            // Tiempos clave (para indicadores)
            $table->dateTime('activation_at')->nullable();       // identificación / activación
            $table->dateTime('antibiotic_at')->nullable();        // administración de antibiótico
            $table->dateTime('fluids_at')->nullable();            // LEV / vasoactivos
            $table->dateTime('drainage_at')->nullable();          // control de la fuente (drenaje)
            $table->dateTime('admission_at')->nullable();
            $table->dateTime('discharged_at')->nullable()->index();
            $table->dateTime('uci_transfer_at')->nullable();
            $table->dateTime('death_at')->nullable();

            // Estancias (en días)
            $table->decimal('er_stay_days', 8, 3)->nullable();    // urgencias
            $table->decimal('clinic_stay_days', 8, 3)->nullable(); // CDO / clínica
            $table->decimal('uci_stay_days', 8, 3)->nullable();    // UCI

            // Indicadores cualitativos
            $table->boolean('culture_taken')->nullable();
            $table->boolean('culture_before_ab')->nullable()->index();
            $table->boolean('ab_compliance')->nullable();
            $table->boolean('ab_adjusted')->nullable()->index();
            $table->boolean('code_activated')->nullable();
            $table->boolean('septic_shock')->nullable();
            $table->boolean('uci')->nullable();
            $table->boolean('deceased')->nullable()->index();
            $table->string('infection_focus')->nullable()->index();
            $table->string('outcome_state')->nullable();

            $table->boolean('is_valid')->default(true)->index();
            $table->boolean('is_recurrence')->default(false)->index();
            $table->boolean('is_cancelled')->default(false)->index();
            $table->text('cancellation_reason')->nullable();

            $table->json('clinical_data')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['month', 'is_valid', 'is_cancelled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_cases');
    }
};
