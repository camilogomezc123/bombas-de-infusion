<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('clinical_program_id')
                ->constrained('sites')->nullOnDelete();
        });

        Schema::table('program_resources', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('clinical_program_id')
                ->constrained('sites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('program_resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });

        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });

        Schema::dropIfExists('sites');
    }
};
