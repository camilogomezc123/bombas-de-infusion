<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->timestamp('culture_at')->nullable()->after('culture_taken');
            $table->timestamp('lactate_at')->nullable()->after('culture_at');
            $table->boolean('map_goal_met')->nullable()->after('septic_shock');
            $table->string('antibiotics_used', 255)->nullable()->after('ab_compliance');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropColumn(['culture_at', 'lactate_at', 'map_goal_met', 'antibiotics_used']);
        });
    }
};
