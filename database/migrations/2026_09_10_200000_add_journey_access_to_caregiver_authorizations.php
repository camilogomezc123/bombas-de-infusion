<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caregiver_authorizations', function (Blueprint $table) {
            $table->boolean('can_access_journey')->default(true)->after('can_write_diary');
        });
    }

    public function down(): void
    {
        Schema::table('caregiver_authorizations', function (Blueprint $table) {
            $table->dropColumn('can_access_journey');
        });
    }
};
