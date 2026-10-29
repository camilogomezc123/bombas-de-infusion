<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caregiver_authorizations', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('relationship');
            $table->boolean('is_emergency_contact')->default(false)->after('is_primary');
            $table->boolean('participates_in_decisions')->default(false)->after('is_emergency_contact');
            $table->string('preferred_language', 40)->nullable()->after('participates_in_decisions');
            $table->string('support_level', 30)->nullable()->after('preferred_language');
            $table->text('communication_notes')->nullable()->after('support_level');
        });
    }

    public function down(): void
    {
        Schema::table('caregiver_authorizations', function (Blueprint $table) {
            $table->dropColumn([
                'is_primary',
                'is_emergency_contact',
                'participates_in_decisions',
                'preferred_language',
                'support_level',
                'communication_notes',
            ]);
        });
    }
};
