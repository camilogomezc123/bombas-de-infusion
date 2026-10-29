<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acv_cases', function (Blueprint $table) {
            $table->string('discharge_destination')->nullable()->after('deceased');
        });
    }

    public function down(): void
    {
        Schema::table('acv_cases', function (Blueprint $table) {
            $table->dropColumn('discharge_destination');
        });
    }
};
