<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raci_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('activity');
            $table->string('responsible');
            $table->string('approver')->nullable();
            $table->string('consulted')->nullable();
            $table->string('informed')->nullable();
            $table->string('service')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raci_assignments');
    }
};
