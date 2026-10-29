<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_postsepsis_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('checkpoint', 20)->index(); // 48_72h | 7d | 15d | 30d | 90d
            $table->date('scheduled_on')->nullable();
            $table->dateTime('contacted_at')->nullable();
            $table->boolean('contact_achieved')->nullable();
            $table->text('clinical_status')->nullable();
            $table->string('functional_status')->nullable();
            $table->string('mobility')->nullable();
            $table->string('cognitive_status')->nullable();
            $table->string('emotional_status')->nullable();
            $table->string('nutrition_status')->nullable();
            $table->boolean('medications_reviewed')->nullable();
            $table->boolean('rehabilitation_needed')->nullable();
            $table->text('social_needs')->nullable();
            $table->boolean('readmission')->nullable();
            $table->boolean('reconsultation')->nullable();
            $table->string('adherence')->nullable();
            $table->text('barriers')->nullable();
            $table->boolean('mortality')->nullable();
            $table->boolean('needs_intervention')->nullable();
            $table->string('recurrence_risk', 20)->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_postsepsis_followups');
    }
};
