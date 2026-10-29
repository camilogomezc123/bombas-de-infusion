<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acv_case_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->boolean('is_effective')->default(false)->index();
            $table->tinyInteger('rankin_90_days')->unsigned()->nullable();
            $table->text('observations')->nullable();
            $table->boolean('is_auto_closed')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_followups');
    }
};
