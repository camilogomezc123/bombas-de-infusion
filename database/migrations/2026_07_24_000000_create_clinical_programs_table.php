<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('short_name');
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('target_population')->nullable();
            $table->text('scope')->nullable();
            $table->text('inclusion_criteria')->nullable();
            $table->text('exclusion_criteria')->nullable();
            $table->string('executive_sponsor')->nullable();
            $table->string('medical_leader')->nullable();
            $table->string('nursing_leader')->nullable();
            $table->string('quality_leader')->nullable();
            $table->date('started_at')->nullable();
            $table->string('status', 40)->default('implementation')->index();
            $table->string('version', 30)->default('1.0');
            $table->timestamp('approved_at')->nullable();
            $table->date('next_review_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('program_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40)->index();
            $table->json('permissions')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['clinical_program_id', 'user_id']);
        });

        DB::table('clinical_programs')->insert([
            'code' => 'SEPSIS',
            'name' => 'Centro Institucional de Excelencia para la Atención Integral de Sepsis y Choque Séptico',
            'short_name' => 'Código Sepsis',
            'status' => 'implementation',
            'version' => '1.0',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('program_members');
        Schema::dropIfExists('clinical_programs');
    }
};
