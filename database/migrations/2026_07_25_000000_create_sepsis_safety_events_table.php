<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_safety_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sepsis_case_id')->nullable()->constrained()->nullOnDelete();
            $table->date('occurred_on')->index();
            $table->string('service')->nullable();
            $table->string('event_type', 60)->index();
            $table->text('description');
            $table->string('harm_level', 40)->nullable();
            $table->string('severity', 40)->index();
            $table->text('immediate_action')->nullable();
            $table->text('analysis')->nullable();
            $table->text('improvement_action')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->string('evidence_reference', 1000)->nullable();
            $table->text('effectiveness_verification')->nullable();
            $table->string('status', 40)->default('open')->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_safety_events');
    }
};
