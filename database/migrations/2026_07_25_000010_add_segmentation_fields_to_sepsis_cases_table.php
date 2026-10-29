<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->string('origin_service')->nullable()->after('admission_number')->index();
            $table->string('acquisition_type', 20)->nullable()->after('origin_service')->index(); // community | hospital
            $table->json('special_population')->nullable()->after('acquisition_type');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropIndex(['origin_service']);
            $table->dropIndex(['acquisition_type']);
        });

        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropColumn(['origin_service', 'acquisition_type', 'special_population']);
        });
    }
};
