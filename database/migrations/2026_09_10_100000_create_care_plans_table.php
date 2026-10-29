<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->date('effective_from')->nullable();
            $table->text('general_objective')->nullable();
            $table->text('discharge_criteria')->nullable();
            $table->json('disciplines')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('pics_case_id');
        });

        Schema::create('care_plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('general_objective')->nullable();
            $table->text('discharge_criteria')->nullable();
            $table->json('disciplines')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plan_versions');
        Schema::dropIfExists('care_plans');
    }
};
