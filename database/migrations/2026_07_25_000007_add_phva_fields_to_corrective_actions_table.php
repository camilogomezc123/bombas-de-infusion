<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrective_actions', function (Blueprint $table) {
            $table->string('phase', 20)->default('do')->after('action')->index(); // plan | do | check | act
            $table->foreignId('indicator_definition_id')->nullable()->after('phase')
                ->constrained('indicator_definitions')->nullOnDelete();
            $table->string('target')->nullable()->after('indicator_definition_id');
            $table->string('frequency')->nullable()->after('target');
            $table->text('evidence_expected')->nullable()->after('frequency');
            $table->string('evidence_file_path')->nullable()->after('evidence_expected');
            $table->unsignedTinyInteger('progress_percentage')->default(0)->after('evidence_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('corrective_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('indicator_definition_id');
            $table->dropIndex(['phase']);
        });

        Schema::table('corrective_actions', function (Blueprint $table) {
            $table->dropColumn(['phase', 'target', 'frequency', 'evidence_expected', 'evidence_file_path', 'progress_percentage']);
        });
    }
};
