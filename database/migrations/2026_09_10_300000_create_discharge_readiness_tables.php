<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discharge_readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('pics_case_id');
        });

        Schema::create('discharge_readiness_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discharge_readiness_check_id')->constrained()->cascadeOnDelete();
            $table->string('topic', 40);
            $table->string('custom_topic')->nullable();
            $table->text('staff_instructions')->nullable();
            $table->string('verification_method', 20)->nullable();
            $table->string('reviewed_by_type')->nullable();
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->boolean('understood')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discharge_readiness_items');
        Schema::dropIfExists('discharge_readiness_checks');
    }
};
