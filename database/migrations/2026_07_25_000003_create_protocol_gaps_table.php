<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description');
            $table->string('protocol_section')->nullable();
            $table->string('risk', 40)->nullable();
            $table->string('priority', 40)->default('medium')->index();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision')->nullable();
            $table->text('proposed_adjustment')->nullable();
            $table->string('status', 40)->default('under_review')->index();
            $table->string('evidence_reference', 1000)->nullable();
            $table->date('closed_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_gaps');
    }
};
