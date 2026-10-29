<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->json('field_status')->nullable()->after('clinical_data');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropColumn('field_status');
        });
    }
};
