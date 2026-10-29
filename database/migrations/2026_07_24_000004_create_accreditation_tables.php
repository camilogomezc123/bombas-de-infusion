<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditation_frameworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('publisher')->nullable();
            $table->string('edition')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->string('reference_url', 1000)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('accreditation_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accreditation_framework_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['accreditation_framework_id', 'code']);
        });
        Schema::create('accreditation_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accreditation_chapter_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['accreditation_chapter_id', 'code']);
        });
        Schema::create('measurable_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accreditation_standard_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description');
            $table->text('institutional_interpretation')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('compliance_status', 40)->default('not_evaluated')->index();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('required_evidence')->nullable();
            $table->date('evaluated_on')->nullable();
            $table->date('next_evaluation_on')->nullable();
            $table->text('observations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['accreditation_standard_id', 'code']);
        });
        Schema::create('compliance_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurable_element_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40)->index();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('observations')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_on')->index();
            $table->date('next_assessment_on')->nullable();
            $table->timestamps();
        });
        Schema::create('evidence_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('evidence_type', 60)->index();
            $table->string('version', 30)->nullable();
            $table->date('approved_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->string('document_reference', 1000);
            $table->text('observations')->nullable();
            $table->boolean('quality_validated')->default(false)->index();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('evidence_document_measurable_element', function (Blueprint $table) {
            $table->foreignId('evidence_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurable_element_id')->constrained()->cascadeOnDelete();
            $table->primary(['evidence_document_id', 'measurable_element_id']);
        });
        Schema::create('assessment_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('severity', 40)->index();
            $table->text('description');
            $table->string('status', 40)->default('open')->index();
            $table->timestamps();
        });
        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_finding_id')->constrained()->cascadeOnDelete();
            $table->text('action');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable()->index();
            $table->string('status', 40)->default('open')->index();
            $table->timestamp('completed_at')->nullable();
            $table->text('effectiveness_verification')->nullable();
            $table->timestamps();
        });

        $programId = DB::table('clinical_programs')->where('code', 'SEPSIS')->value('id');
        $frameworkId = DB::table('accreditation_frameworks')->insertGetId([
            'clinical_program_id' => $programId,
            'name' => 'Marco de certificación de programas clínicos',
            'publisher' => 'Joint Commission International',
            'edition' => 'Cuarta edición',
            'effective_from' => '2024-01-01',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['PART', 'Requisitos de participación'],
            ['LEAD', 'Liderazgo y gestión'],
            ['CARE', 'Prestación de la atención clínica'],
            ['SELF', 'Paciente, familia y autocuidado'],
            ['INFO', 'Gestión de la información clínica'],
            ['IMPR', 'Medición y mejoramiento'],
        ] as $index => [$code, $name]) {
            DB::table('accreditation_chapters')->insert([
                'accreditation_framework_id' => $frameworkId,
                'code' => $code,
                'name' => $name,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
        Schema::dropIfExists('assessment_findings');
        Schema::dropIfExists('evidence_document_measurable_element');
        Schema::dropIfExists('evidence_documents');
        Schema::dropIfExists('compliance_assessments');
        Schema::dropIfExists('measurable_elements');
        Schema::dropIfExists('accreditation_standards');
        Schema::dropIfExists('accreditation_chapters');
        Schema::dropIfExists('accreditation_frameworks');
    }
};
