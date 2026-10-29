<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_postsepsis_followups', function (Blueprint $table) {
            $table->string('readmission_reason')->nullable()->after('readmission');
            $table->string('cognitive_instrument')->nullable()->after('cognitive_status');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_postsepsis_followups', function (Blueprint $table) {
            $table->dropColumn(['readmission_reason', 'cognitive_instrument']);
        });
    }
};
