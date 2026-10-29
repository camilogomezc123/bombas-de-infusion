<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_program_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version')->default('1.0');
            $table->json('configuration')->nullable();
            $table->string('source_document')->nullable();
            $table->string('source_section')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['clinical_program_id', 'code', 'version']);
        });

        $programId = DB::table('clinical_programs')->where('code', 'INFARTO')->value('id');
        foreach ([
            ['SCA-ECG-10', 'ECG interpretado en máximo 10 minutos', ['target_minutes' => 10]],
            ['STEMI-FMC-90', 'FMC a primer dispositivo en ingreso directo', ['target_minutes' => 90]],
            ['STEMI-FMC-120', 'FMC a primer dispositivo en traslado', ['target_minutes' => 120]],
            ['NSTE-ANGIO-24H', 'Angiografía oportuna en estrategia invasiva temprana', ['target_minutes' => 1440]],
            ['NSTE-GRACE', 'Valoración GRACE documentada', ['required_fields' => ['grace_score', 'grace_risk_category', 'grace_assessed_at']]],
        ] as [$code, $name, $configuration]) {
            DB::table('clinical_rules')->insert([
                'clinical_program_id' => $programId, 'code' => $code, 'name' => $name,
                'version' => '1.0', 'configuration' => json_encode($configuration),
                'source_document' => 'Pendiente de adopción institucional de la guía ACC/AHA 2025',
                'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_rules');
    }
};
