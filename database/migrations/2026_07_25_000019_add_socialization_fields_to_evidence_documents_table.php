<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_documents', function (Blueprint $table) {
            $table->string('institutional_code', 60)->nullable()->after('title');
            $table->date('next_review_on')->nullable()->after('expires_on');
            $table->string('socialization_status', 20)->default('pending')->after('next_review_on');
        });

        Schema::create('evidence_document_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_member_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20)->default('read'); // read | training
            $table->date('acknowledged_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['evidence_document_id', 'program_member_id'], 'evidence_ack_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_document_acknowledgements');

        Schema::table('evidence_documents', function (Blueprint $table) {
            $table->dropColumn(['institutional_code', 'next_review_on', 'socialization_status']);
        });
    }
};
