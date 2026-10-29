<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', fn (Blueprint $table) => $table->string('portal_username')->nullable()->unique());
        Schema::table('caregivers', fn (Blueprint $table) => $table->string('portal_username')->nullable()->unique());
    }
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['portal_username']);
            $table->dropColumn('portal_username');
        });
        Schema::table('caregivers', function (Blueprint $table) {
            $table->dropUnique(['portal_username']);
            $table->dropColumn('portal_username');
        });
    }
};
