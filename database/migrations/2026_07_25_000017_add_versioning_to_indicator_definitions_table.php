<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicator_definitions', function (Blueprint $table) {
            $table->text('exclusion_criteria')->nullable()->after('denominator');
            $table->text('validation_method')->nullable()->after('source');
            $table->unsignedInteger('version')->default(1)->after('sort_order');
            $table->date('effective_from')->nullable()->after('version');
        });

        Schema::create('indicator_definition_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('numerator')->nullable();
            $table->text('denominator')->nullable();
            $table->text('exclusion_criteria')->nullable();
            $table->text('formula')->nullable();
            $table->text('validation_method')->nullable();
            $table->string('target_value')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_definition_versions');

        Schema::table('indicator_definitions', function (Blueprint $table) {
            $table->dropColumn(['exclusion_criteria', 'validation_method', 'version', 'effective_from']);
        });
    }
};
