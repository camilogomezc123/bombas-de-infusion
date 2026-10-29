<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_progress_reports', function (Blueprint $table) {
            $table->string('difficulty_reason', 30)->nullable()->after('had_difficulty');
            $table->string('difficulty_reason_other')->nullable()->after('difficulty_reason');
        });

        Schema::create('recovery_passports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->text('mobility_before')->nullable();
            $table->text('autonomy_before')->nullable();
            $table->text('habitual_activities')->nullable();
            $table->text('supports_before')->nullable();
            $table->text('current_situation')->nullable();
            $table->text('home_barriers')->nullable();
            $table->text('transport_barriers')->nullable();
            $table->text('companion_barriers')->nullable();
            $table->text('access_barriers')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reported_by_type')->nullable();
            $table->unsignedBigInteger('reported_by_id')->nullable();
            $table->dateTime('reported_at')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique('pics_case_id');
        });

        Schema::create('recovery_passport_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recovery_passport_id')->constrained()->cascadeOnDelete();
            $table->string('type', 25); // necesidad | objetivo_significativo
            $table->text('description');
            $table->string('status', 20)->nullable(); // identificado | en_progreso | resuelto (solo necesidad)
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pics_case_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('dificultad');
            $table->text('description');
            $table->string('priority', 10)->default('media'); // baja | media | alta
            $table->string('status', 15)->default('nueva')->index(); // nueva|asignada|reconocida|respondida|escalada|resuelta
            $table->string('created_by_type');
            $table->unsignedBigInteger('created_by_id');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_deadline')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->text('response_text')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('responded_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
        Schema::dropIfExists('recovery_passport_items');
        Schema::dropIfExists('recovery_passports');

        Schema::table('goal_progress_reports', function (Blueprint $table) {
            $table->dropColumn(['difficulty_reason', 'difficulty_reason_other']);
        });
    }
};
