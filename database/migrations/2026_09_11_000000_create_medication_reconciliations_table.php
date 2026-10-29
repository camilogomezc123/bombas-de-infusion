<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('pics_case_id');
        });

        Schema::create('medication_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('dose')->nullable();
            $table->string('route', 20)->nullable();
            $table->string('frequency')->nullable();
            $table->string('status', 15)->default('continua');
            $table->text('reconciliation_notes')->nullable();
            $table->text('patient_instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_reconciliation_items');
        Schema::dropIfExists('medication_reconciliations');
    }
};
