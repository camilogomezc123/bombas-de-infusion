<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('portal_easy_mode')->default(false)->after('has_seen_portal_tour');
        });

        Schema::table('caregivers', function (Blueprint $table) {
            $table->boolean('portal_easy_mode')->default(false)->after('has_seen_portal_tour');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('portal_easy_mode');
        });

        Schema::table('caregivers', function (Blueprint $table) {
            $table->dropColumn('portal_easy_mode');
        });
    }
};
