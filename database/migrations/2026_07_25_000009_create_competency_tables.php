<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role_key')->nullable();
            $table->text('description')->nullable();
            $table->string('training_method')->nullable();
            $table->string('evaluation_method')->nullable();
            $table->decimal('minimum_score', 5, 2)->nullable();
            $table->string('periodicity')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_member_id')->constrained()->cascadeOnDelete();
            $table->date('approved_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('result', 40)->nullable();
            $table->string('evidence_reference', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_competencies');
        Schema::dropIfExists('competencies');
    }
};
