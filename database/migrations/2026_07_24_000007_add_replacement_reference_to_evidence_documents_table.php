<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_documents', function (Blueprint $table) {
            $table->foreignId('replaces_evidence_document_id')->nullable()->after('id')
                ->constrained('evidence_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evidence_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_evidence_document_id');
        });
    }
};
