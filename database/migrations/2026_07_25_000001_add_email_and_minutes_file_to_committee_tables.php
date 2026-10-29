<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('committee_members', function (Blueprint $table) {
            $table->string('email')->nullable()->after('display_name');
        });

        Schema::table('committee_meetings', function (Blueprint $table) {
            $table->string('minutes_file_path')->nullable()->after('minutes');
        });
    }

    public function down(): void
    {
        Schema::table('committee_meetings', function (Blueprint $table) {
            $table->dropColumn('minutes_file_path');
        });

        Schema::table('committee_members', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
