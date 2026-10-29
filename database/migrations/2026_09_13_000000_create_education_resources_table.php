<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 20)->nullable();
            $table->string('audience', 15)->default('ambos');
            $table->text('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('education_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('viewed_by_type')->nullable();
            $table->unsignedBigInteger('viewed_by_id')->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->timestamps();
            $table->unique(['pics_case_id', 'education_resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_assignments');
        Schema::dropIfExists('education_resources');
    }
};
