<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_cultures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('site');
            $table->dateTime('taken_at')->nullable()->index();
            $table->string('microorganism')->nullable();
            $table->text('susceptibility')->nullable();
            $table->boolean('contamination')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_cultures');
    }
};
