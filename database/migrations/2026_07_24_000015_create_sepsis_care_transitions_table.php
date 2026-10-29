<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_care_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('origin_service');
            $table->string('destination_service');
            $table->dateTime('transitioned_at')->index();
            $table->string('handing_clinician')->nullable();
            $table->string('receiving_clinician')->nullable();
            $table->text('clinical_status')->nullable();
            $table->text('pending_items')->nullable();
            $table->boolean('bundle_pending')->nullable();
            $table->text('active_antimicrobials')->nullable();
            $table->boolean('source_control_pending')->nullable();
            $table->boolean('icu_needed')->nullable();
            $table->boolean('discharge')->default(false);
            $table->text('post_sepsis_plan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_care_transitions');
    }
};
