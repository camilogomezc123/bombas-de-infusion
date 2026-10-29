<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_source_control_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('specialty')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('assessed_at')->nullable();
            $table->string('decision')->nullable();
            $table->string('procedure')->nullable();
            $table->dateTime('performed_at')->nullable()->index();
            $table->text('barriers')->nullable();
            $table->text('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_source_control_actions');
    }
};
