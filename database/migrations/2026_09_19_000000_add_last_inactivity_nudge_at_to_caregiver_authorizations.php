<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caregiver_authorizations', function (Blueprint $table) {
            $table->timestamp('last_inactivity_nudge_at')->nullable()->after('authorized_at');
        });
    }

    public function down(): void
    {
        Schema::table('caregiver_authorizations', function (Blueprint $table) {
            $table->dropColumn('last_inactivity_nudge_at');
        });
    }
};
