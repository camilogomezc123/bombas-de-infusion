<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_members', function (Blueprint $table) {
            $table->string('discipline')->nullable()->after('role');
            $table->string('service')->nullable()->after('discipline');
            $table->string('route_role')->nullable()->after('service');
            $table->text('required_competencies')->nullable()->after('route_role');
            $table->text('evaluated_competencies')->nullable()->after('required_competencies');
            $table->date('competency_evaluated_on')->nullable()->after('evaluated_competencies');
            $table->string('competency_result', 40)->nullable()->after('competency_evaluated_on');
            $table->date('competency_valid_until')->nullable()->after('competency_result')->index();
            $table->boolean('retraining_required')->default(false)->after('competency_valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('program_members', function (Blueprint $table) {
            $table->dropIndex(['competency_valid_until']);
        });

        Schema::table('program_members', function (Blueprint $table) {
            $table->dropColumn([
                'discipline',
                'service',
                'route_role',
                'required_competencies',
                'evaluated_competencies',
                'competency_evaluated_on',
                'competency_result',
                'competency_valid_until',
                'retraining_required',
            ]);
        });
    }
};
