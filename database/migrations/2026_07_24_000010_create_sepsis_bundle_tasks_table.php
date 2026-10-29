<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepsis_bundle_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sepsis_case_id')->constrained()->cascadeOnDelete();
            $table->string('task_key', 60)->index();
            $table->string('label');
            $table->string('status', 40)->default('pending')->index();
            $table->dateTime('target_at')->nullable();
            $table->dateTime('done_at')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->nullable();
            $table->text('result')->nullable();
            $table->text('justification')->nullable();
            $table->boolean('escalated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepsis_bundle_tasks');
    }
};
