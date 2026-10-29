<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->index();
            $table->string('name');
            $table->string('indicator_group', 40)->index(); // structure | process | result | experience
            $table->string('type')->nullable();
            $table->text('objective')->nullable();
            $table->text('operational_definition')->nullable();
            $table->text('numerator')->nullable();
            $table->text('denominator')->nullable();
            $table->text('formula')->nullable();
            $table->string('unit', 40)->nullable();
            $table->string('target_value')->nullable();
            $table->string('expected_direction', 20)->nullable(); // higher_better | lower_better
            $table->string('source')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('periodicity', 40)->nullable();
            $table->string('baseline_value')->nullable();
            $table->boolean('is_core_indicator')->default(false)->index();
            $table->string('core_indicator_key', 60)->nullable();
            $table->string('current_result')->nullable();
            $table->string('trend', 20)->nullable(); // up | down | stable
            $table->text('observations')->nullable();
            $table->timestamp('result_updated_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['clinical_program_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_definitions');
    }
};
