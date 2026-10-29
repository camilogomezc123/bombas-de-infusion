<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_antimicrobial_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('drug');
            $table->string('dose')->nullable();
            $table->string('route')->nullable();
            $table->dateTime('ordered_at')->nullable();
            $table->dateTime('dispensed_at')->nullable();
            $table->dateTime('administered_at')->nullable()->index();
            $table->boolean('renal_adjustment')->nullable();
            $table->boolean('allergy_checked')->nullable();
            $table->text('resistance_factors')->nullable();
            $table->boolean('proa_review')->nullable();
            $table->string('adjustment_or_deescalation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_antimicrobial_administrations');
    }
};
