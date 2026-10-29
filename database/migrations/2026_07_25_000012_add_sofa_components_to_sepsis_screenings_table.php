<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_screenings', function (Blueprint $table) {
            $table->unsignedTinyInteger('sofa_respiratory')->nullable()->after('sofa_score');
            $table->unsignedTinyInteger('sofa_coagulation')->nullable()->after('sofa_respiratory');
            $table->unsignedTinyInteger('sofa_liver')->nullable()->after('sofa_coagulation');
            $table->unsignedTinyInteger('sofa_cardiovascular')->nullable()->after('sofa_liver');
            $table->unsignedTinyInteger('sofa_cns')->nullable()->after('sofa_cardiovascular');
            $table->unsignedTinyInteger('sofa_renal')->nullable()->after('sofa_cns');
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_screenings', function (Blueprint $table) {
            $table->dropColumn(['sofa_respiratory', 'sofa_coagulation', 'sofa_liver', 'sofa_cardiovascular', 'sofa_cns', 'sofa_renal']);
        });
    }
};
