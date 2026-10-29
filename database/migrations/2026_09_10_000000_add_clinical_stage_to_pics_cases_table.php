<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pics_cases', function (Blueprint $table) {
            $table->string('clinical_stage', 20)->default('uci')->after('status')->index();
            $table->dateTime('uci_started_at')->nullable()->after('clinical_stage');
            $table->dateTime('hospitalization_started_at')->nullable()->after('uci_started_at');
            $table->dateTime('discharge_confirmed_at')->nullable()->after('hospitalization_started_at');
            $table->foreignId('discharge_confirmed_by')->nullable()->after('discharge_confirmed_at')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('followup_started_at')->nullable()->after('discharge_confirmed_by');
        });
    }

    public function down(): void
    {
        // SQLite exige quitar la clave foránea y el índice antes de poder eliminar las
        // columnas que definen.
        Schema::table('pics_cases', function (Blueprint $table) {
            $table->dropForeign(['discharge_confirmed_by']);
            $table->dropIndex(['clinical_stage']);
        });

        Schema::table('pics_cases', function (Blueprint $table) {
            $table->dropColumn([
                'clinical_stage', 'uci_started_at', 'hospitalization_started_at',
                'discharge_confirmed_at', 'discharge_confirmed_by', 'followup_started_at',
            ]);
        });
    }
};
