<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pics_agenda_items', function (Blueprint $table) {
            $table->string('patient_response', 20)->nullable()->after('status');
            $table->dateTime('patient_response_at')->nullable()->after('patient_response');
            $table->string('patient_responded_by_type')->nullable()->after('patient_response_at');
            $table->unsignedBigInteger('patient_responded_by_id')->nullable()->after('patient_responded_by_type');
        });
    }

    public function down(): void
    {
        Schema::table('pics_agenda_items', function (Blueprint $table) {
            $table->dropColumn(['patient_response', 'patient_response_at', 'patient_responded_by_type', 'patient_responded_by_id']);
        });
    }
};
