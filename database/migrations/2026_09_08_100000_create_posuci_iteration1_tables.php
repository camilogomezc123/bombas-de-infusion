<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('full_name');
            $table->string('password')->nullable()->after('email');
            $table->boolean('must_change_password')->default(true)->after('password');
            $table->rememberToken()->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        Schema::create('caregivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('caregiver_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('caregiver_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->nullable();
            $table->boolean('can_write_diary')->default(true);
            $table->foreignId('authorized_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('authorized_at');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['pics_case_id', 'caregiver_id']);
        });

        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->string('authorable_type');
            $table->unsignedBigInteger('authorable_id');
            $table->date('entry_date');
            $table->text('content');
            $table->text('message_to_patient')->nullable();
            $table->text('meaningful_memory')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->boolean('visible_to_patient')->default(true);
            $table->timestamps();
            $table->index(['authorable_type', 'authorable_id']);
        });

        Schema::create('recovery_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->text('description');
            $table->string('measure')->nullable();
            $table->string('unit')->nullable();
            $table->string('assistance_level')->nullable();
            $table->date('target_date')->nullable();
            $table->text('restrictions')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_criteria')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('goal_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recovery_goal_id')->constrained()->cascadeOnDelete();
            $table->string('reporter_type');
            $table->unsignedBigInteger('reporter_id');
            $table->dateTime('reported_at');
            $table->text('notes')->nullable();
            $table->boolean('had_difficulty')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();
            $table->index(['reporter_type', 'reporter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress_reports');
        Schema::dropIfExists('recovery_goals');
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('caregiver_authorizations');
        Schema::dropIfExists('caregivers');

        // SQLite exige quitar el índice único antes de poder eliminar la columna que indexa.
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['email', 'password', 'must_change_password', 'remember_token', 'last_login_at']);
        });
    }
};
