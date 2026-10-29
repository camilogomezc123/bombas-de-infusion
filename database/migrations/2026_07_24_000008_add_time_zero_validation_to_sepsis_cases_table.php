<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->timestamp('time_zero_validated_at')->nullable()->after('activation_at');
            $table->foreignId('time_zero_validated_by')->nullable()->after('time_zero_validated_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('time_zero_validated_by');
            $table->dropColumn('time_zero_validated_at');
        });
    }
};
