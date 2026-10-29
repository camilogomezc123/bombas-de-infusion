<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_monitoring_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->string('reading_type', 20);
            $table->string('value');
            $table->string('unit', 20)->nullable();
            $table->dateTime('measured_at');
            $table->text('notes')->nullable();
            $table->string('recorded_by_type')->nullable();
            $table->unsignedBigInteger('recorded_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_monitoring_readings');
    }
};
