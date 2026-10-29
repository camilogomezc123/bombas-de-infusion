<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pics_cases', function (Blueprint $table) {
            $table->timestamp('last_inactivity_alert_at')->nullable()->after('followup_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('pics_cases', function (Blueprint $table) {
            $table->dropColumn('last_inactivity_alert_at');
        });
    }
};
