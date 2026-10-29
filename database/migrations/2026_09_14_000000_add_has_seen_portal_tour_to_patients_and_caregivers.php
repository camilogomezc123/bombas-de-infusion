<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('has_seen_portal_tour')->default(false)->after('must_change_password');
        });

        Schema::table('caregivers', function (Blueprint $table) {
            $table->boolean('has_seen_portal_tour')->default(false)->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('has_seen_portal_tour');
        });

        Schema::table('caregivers', function (Blueprint $table) {
            $table->dropColumn('has_seen_portal_tour');
        });
    }
};
