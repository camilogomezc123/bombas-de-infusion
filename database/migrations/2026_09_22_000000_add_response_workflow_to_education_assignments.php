<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_assignments', function (Blueprint $table) {
            $table->string('response', 30)->nullable()->after('viewed_at');
            $table->text('feedback')->nullable()->after('response');
            $table->string('completed_by_type')->nullable()->after('feedback');
            $table->unsignedBigInteger('completed_by_id')->nullable()->after('completed_by_type');
            $table->dateTime('completed_at')->nullable()->after('completed_by_id');
            $table->foreignId('validated_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable()->after('validated_by');
            $table->text('validation_notes')->nullable()->after('validated_at');

            $table->index(
                ['completed_by_type', 'completed_by_id'],
                'education_assignments_completed_by_index',
            );
            $table->index('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('education_assignments', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropIndex('education_assignments_completed_by_index');
            $table->dropIndex(['validated_at']);
            $table->dropColumn([
                'response',
                'feedback',
                'completed_by_type',
                'completed_by_id',
                'completed_at',
                'validated_by',
                'validated_at',
                'validation_notes',
            ]);
        });
    }
};
