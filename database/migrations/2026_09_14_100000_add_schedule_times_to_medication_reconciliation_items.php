<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_reconciliation_items', function (Blueprint $table) {
            $table->json('schedule_times')->nullable()->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('medication_reconciliation_items', function (Blueprint $table) {
            $table->dropColumn('schedule_times');
        });
    }
};
