<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('purpose')->nullable();
            $table->string('status', 40)->default('active')->index();
            $table->timestamps();
        });
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_committee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->string('discipline')->nullable();
            $table->string('committee_role')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('committee_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_committee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTimeTz('scheduled_at')->index();
            $table->dateTimeTz('ended_at')->nullable();
            $table->string('status', 40)->default('scheduled')->index();
            $table->text('agenda')->nullable();
            $table->text('minutes')->nullable();
            $table->timestamps();
        });
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('committee_member_id')->constrained()->cascadeOnDelete();
            $table->boolean('attended')->default(false);
            $table->timestamps();
            $table->unique(['committee_meeting_id', 'committee_member_id']);
        });
        Schema::create('meeting_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_meeting_id')->constrained()->cascadeOnDelete();
            $table->text('decision');
            $table->text('rationale')->nullable();
            $table->timestamps();
        });
        Schema::create('meeting_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_decision_id')->nullable()->constrained()->nullOnDelete();
            $table->text('action');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsible_name')->nullable();
            $table->date('due_on')->nullable()->index();
            $table->string('status', 40)->default('open')->index();
            $table->string('closure_evidence_reference', 1000)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_actions');
        Schema::dropIfExists('meeting_decisions');
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('committee_meetings');
        Schema::dropIfExists('committee_members');
        Schema::dropIfExists('program_committees');
    }
};
