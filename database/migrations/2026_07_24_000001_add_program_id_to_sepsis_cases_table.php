<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $programId = DB::table('clinical_programs')->where('code', 'SEPSIS')->value('id');

        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->foreignId('clinical_program_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
            $table->index(['clinical_program_id', 'status']);
        });

        DB::table('sepsis_cases')
            ->whereNull('clinical_program_id')
            ->update(['clinical_program_id' => $programId]);
    }

    public function down(): void
    {
        Schema::table('sepsis_cases', function (Blueprint $table) {
            $table->dropIndex(['clinical_program_id', 'status']);
            $table->dropConstrainedForeignId('clinical_program_id');
        });
    }
};
