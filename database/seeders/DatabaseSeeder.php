<?php

namespace Database\Seeders;

use App\Enums\EvidenceStatus;
use App\Enums\UserRole;
use App\Models\ClinicalProgram;
use App\Models\Competency;
use App\Models\EvidenceDocument;
use App\Models\IndicatorDefinition;
use App\Models\IndicatorTarget;
use App\Models\ProtocolGap;
use App\Models\RaciAssignment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (filled(env('AGORA_ADMIN_USERNAME')) && filled(env('AGORA_ADMIN_PASSWORD'))) {
            User::query()->updateOrCreate(
                ['username' => mb_strtoupper(env('AGORA_ADMIN_USERNAME'))],
                [
                    'name' => env('AGORA_ADMIN_NAME', 'Administrador ÁGORA'),
                    'email' => env('AGORA_ADMIN_EMAIL'),
                    'role' => UserRole::Administrator,
                    'is_active' => true,
                    'must_change_password' => true,
                    'password' => env('AGORA_ADMIN_PASSWORD'),
                ],
            );
        }

        $targets = [
            ['key' => 'door_to_needle_60', 'name' => 'Puerta-aguja antes de 60 min', 'unit' => '%', 'warning_value' => 75, 'target_value' => 85, 'comparison' => 'gte'],
            ['key' => 'door_to_needle_45', 'name' => 'Puerta-aguja antes de 45 min', 'unit' => '%', 'warning_value' => 60, 'target_value' => 75, 'comparison' => 'gte'],
            ['key' => 'door_to_groin_120', 'name' => 'Puerta-ingle antes de 120 min', 'unit' => '%', 'warning_value' => 70, 'target_value' => 80, 'comparison' => 'gte'],
            ['key' => 'door_to_groin_90', 'name' => 'Puerta-ingle antes de 90 min', 'unit' => '%', 'warning_value' => 40, 'target_value' => 50, 'comparison' => 'gte'],
            ['key' => 'hemorrhagic_transformation', 'name' => 'Transformación hemorrágica', 'unit' => '%', 'warning_value' => 7, 'target_value' => 5, 'comparison' => 'lte'],
            ['key' => 'speech_therapy_compliance', 'name' => 'Valoración por fonoaudiología', 'unit' => '%', 'warning_value' => 70, 'target_value' => 80, 'comparison' => 'gte'],
        ];

        foreach ($targets as $target) {
            IndicatorTarget::query()->updateOrCreate(['key' => $target['key']], $target);
        }

        $sepsisProgram = ClinicalProgram::query()->where('code', 'SEPSIS')->first();

        if ($sepsisProgram) {
            $this->seedProtocolGaps($sepsisProgram);
            $this->seedIndicatorCatalog($sepsisProgram);
            $this->seedPrincipalDocuments($sepsisProgram);
            $this->seedRaciMatrix($sepsisProgram);
            $this->seedCompetencyCatalog($sepsisProgram);
        }
    }

    /**
     * "Brechas y ajustes del protocolo" — puntos reales para revisión editorial y clínica
     * por el Comité Institucional de Sepsis. No se modifican recomendaciones clínicas
     * automáticamente; solo se deja constancia de la brecha para su decisión formal.
     */
    private function seedProtocolGaps(ClinicalProgram $program): void
    {
        $gaps = [
            'La introducción del protocolo de adultos contiene una referencia pediátrica.',
            'Existen diferencias entre el criterio de activación NEWS2 mayor o igual a 6 y otros signos clínicos de activación.',
            'El tiempo del antimicrobiano debe diferenciar choque, sepsis probable y sepsis posible sin choque.',
            'Debe armonizarse la estrategia de líquidos y eliminar instrucciones contradictorias.',
            'Debe revisarse el límite absoluto de una hora para vasopresores periféricos.',
            'Debe revisarse la temperatura menor de 37,3 grados como meta universal.',
            'Debe unificarse la codificación diagnóstica.',
            'Debe definirse una única hora cero institucional.',
            'Debe diferenciarse activación clínica de codificación administrativa.',
            'Debe desarrollarse la recuperación postsepsis.',
        ];

        foreach ($gaps as $index => $description) {
            ProtocolGap::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'description' => $description],
                ['sort_order' => $index + 1, 'priority' => 'medium', 'status' => 'under_review'],
            );
        }
    }

    /**
     * Ficha técnica de indicadores. Los 6 institucionales (is_core_indicator=true) se
     * marcan con su core_indicator_key: su resultado se lee en vivo de SepsisIndicatorService,
     * nunca se recalcula ni se guarda duplicado aquí. Los demás son indicadores de
     * estructura/experiencia/sostenibilidad que hoy no tienen fuente de cálculo automática.
     */
    private function seedIndicatorCatalog(ClinicalProgram $program): void
    {
        $core = [
            ['code' => 'PROC-01', 'name' => 'Adherencia al bundle de primera hora', 'key' => 'bundle_pct', 'target' => '≥ 70%'],
            ['code' => 'PROC-02', 'name' => 'Meta de PAM en las primeras 3 horas', 'key' => 'map_goal_pct', 'target' => '≥ 70%'],
            ['code' => 'RES-01', 'name' => 'Mortalidad hospitalaria por sepsis', 'key' => 'mort_hosp_sepsis_pct', 'target' => '< 17,5%'],
            ['code' => 'RES-02', 'name' => 'Mortalidad hospitalaria por choque séptico', 'key' => 'mort_hosp_shock_pct', 'target' => '≤ 60%'],
            ['code' => 'RES-03', 'name' => 'Mortalidad a 30 días por sepsis', 'key' => 'mort_30d_sepsis_pct', 'target' => '≤ 30%'],
            ['code' => 'RES-04', 'name' => 'Mortalidad a 30 días por choque séptico', 'key' => 'mort_30d_shock_pct', 'target' => '≤ 60%'],
        ];

        foreach ($core as $index => $indicator) {
            IndicatorDefinition::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'code' => $indicator['code']],
                [
                    'name' => $indicator['name'],
                    'indicator_group' => str_starts_with($indicator['code'], 'PROC') ? 'process' : 'result',
                    'is_core_indicator' => true,
                    'core_indicator_key' => $indicator['key'],
                    'target_value' => $indicator['target'],
                    'source' => 'Módulo de Indicadores (SepsisIndicatorService)',
                    'periodicity' => 'Mensual',
                    'sort_order' => $index + 1,
                ],
            );
        }

        $additional = [
            ['code' => 'EST-01', 'name' => 'Comité Institucional de Sepsis activo', 'group' => 'structure', 'target' => 'Activo'],
            ['code' => 'EST-02', 'name' => 'Cumplimiento de reuniones del comité', 'group' => 'structure', 'target' => '≥ 80%'],
            ['code' => 'EST-03', 'name' => 'Servicios con líder designado', 'group' => 'structure', 'target' => '100%'],
            ['code' => 'EST-04', 'name' => 'Disponibilidad de maletas de Código Sepsis', 'group' => 'structure', 'target' => '100%'],
            ['code' => 'EST-05', 'name' => 'Personal con competencia vigente', 'group' => 'structure', 'target' => '≥ 90%'],
            ['code' => 'PROC-03', 'name' => 'Activación documentada del Código Sepsis', 'group' => 'process', 'target' => '≥ 80%'],
            ['code' => 'PROC-04', 'name' => 'Lactato oportuno', 'group' => 'process', 'target' => '≥ 85%'],
            ['code' => 'PROC-05', 'name' => 'Hemocultivos previos al antimicrobiano cuando aplique', 'group' => 'process', 'target' => '≥ 80%'],
            ['code' => 'PROC-06', 'name' => 'Control oportuno de la fuente', 'group' => 'process', 'target' => '≥ 75%'],
            ['code' => 'PROC-07', 'name' => 'Revisión antimicrobiana a las 48-72 horas', 'group' => 'process', 'target' => 'Meta inicial, revisar tras línea base'],
            ['code' => 'PROC-08', 'name' => 'Seguimiento postsepsis programado', 'group' => 'process', 'target' => 'Meta inicial, revisar tras línea base'],
            ['code' => 'RES-05', 'name' => 'Ingreso no planeado a UCI', 'group' => 'result', 'target' => 'Sin meta fija — análisis ajustado por riesgo'],
            ['code' => 'RES-06', 'name' => 'Reingreso a 30 días', 'group' => 'result', 'target' => 'Sin meta fija — análisis ajustado por riesgo'],
            ['code' => 'EXP-01', 'name' => 'Comprensión de la información por el paciente y la familia', 'group' => 'experience', 'target' => 'Meta inicial, revisar tras línea base'],
            ['code' => 'EXP-02', 'name' => 'Acciones de mejora cerradas con evidencia', 'group' => 'experience', 'target' => 'Meta inicial, revisar tras línea base'],
            ['code' => 'EXP-03', 'name' => 'Recurrencia de retrasos', 'group' => 'experience', 'target' => 'Meta inicial, revisar tras línea base'],
        ];

        foreach ($additional as $index => $indicator) {
            IndicatorDefinition::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'code' => $indicator['code']],
                [
                    'name' => $indicator['name'],
                    'indicator_group' => $indicator['group'],
                    'is_core_indicator' => false,
                    'target_value' => $indicator['target'],
                    'periodicity' => 'Mensual',
                    'sort_order' => 100 + $index,
                    'observations' => 'Meta inicial configurable; debe revisarse después de construir la línea base institucional.',
                ],
            );
        }
    }

    /**
     * Metadatos de los documentos principales del programa (sin archivo real cargado
     * todavía). Se pueden adjuntar posteriormente desde Programa → Guías y documentos.
     */
    private function seedPrincipalDocuments(ClinicalProgram $program): void
    {
        $documents = [
            ['title' => 'Manual del Programa Institucional de Excelencia en Sepsis', 'type' => 'administrative_document'],
            ['title' => 'Protocolo Institucional Código Sepsis en Adultos', 'type' => 'clinical_guideline'],
            ['title' => 'Carta Constitutiva del Programa de Sepsis', 'type' => 'policy'],
            ['title' => 'Reglamento del Comité Institucional de Sepsis', 'type' => 'policy'],
            ['title' => 'Ruta de Control de la Fuente', 'type' => 'protocol'],
            ['title' => 'Ruta de Recuperación Postsepsis', 'type' => 'protocol'],
            ['title' => 'Guía Antimicrobiana Institucional', 'type' => 'clinical_guideline'],
            ['title' => 'Matriz RACI del Programa de Sepsis', 'type' => 'administrative_document'],
            ['title' => 'Matriz de Competencias del Talento Humano', 'type' => 'administrative_document'],
            ['title' => 'Diccionario de Datos del Programa de Sepsis', 'type' => 'administrative_document'],
            ['title' => 'Fichas Técnicas de Indicadores', 'type' => 'administrative_document'],
            ['title' => 'Plan Anual de Educación', 'type' => 'administrative_document'],
            ['title' => 'Plan Anual de Mejoramiento', 'type' => 'administrative_document'],
            ['title' => 'Informe Anual de Resultados', 'type' => 'report'],
        ];

        foreach ($documents as $document) {
            EvidenceDocument::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'title' => $document['title']],
                [
                    'evidence_type' => $document['type'],
                    'status' => EvidenceStatus::Draft,
                    'version' => '0.1',
                    'document_reference' => 'Pendiente de cargar — ver README para instrucciones de carga del documento fuente.',
                    'observations' => 'Registro documental inicial generado automáticamente. Reemplazar por el archivo real cuando esté disponible.',
                ],
            );
        }
    }

    private function seedRaciMatrix(ClinicalProgram $program): void
    {
        $rows = [
            ['activity' => 'Activación del Código Sepsis', 'responsible' => 'Médico tratante', 'approver' => 'Coordinador del programa', 'consulted' => 'Enfermería', 'informed' => 'Comité Institucional de Sepsis'],
            ['activity' => 'Validación del tiempo cero', 'responsible' => 'Coordinador del programa', 'approver' => 'Director médico', 'consulted' => 'Auditor', 'informed' => 'Calidad'],
            ['activity' => 'Auditoría de casos', 'responsible' => 'Auditor', 'approver' => 'Coordinador del programa', 'consulted' => 'Calidad', 'informed' => 'Comité Institucional de Sepsis'],
            ['activity' => 'Formulación de planes PHVA', 'responsible' => 'Gestor de calidad', 'approver' => 'Comité Institucional de Sepsis', 'consulted' => 'Servicios involucrados', 'informed' => 'Dirección'],
            ['activity' => 'Revisión de indicadores', 'responsible' => 'Analista de datos', 'approver' => 'Coordinador del programa', 'consulted' => 'Calidad', 'informed' => 'Dirección'],
            ['activity' => 'Actualización del protocolo institucional', 'responsible' => 'Director médico', 'approver' => 'Comité Institucional de Sepsis', 'consulted' => 'Infectología, Farmacia', 'informed' => 'Todos los servicios participantes'],
            ['activity' => 'Seguimiento postsepsis', 'responsible' => 'Enfermería / Coordinador del programa', 'approver' => 'Codirector de enfermería', 'consulted' => 'Rehabilitación', 'informed' => 'Médico tratante'],
        ];

        foreach ($rows as $row) {
            RaciAssignment::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'activity' => $row['activity']],
                $row,
            );
        }
    }

    private function seedCompetencyCatalog(ClinicalProgram $program): void
    {
        $competencies = [
            ['name' => 'Reconocimiento temprano y activación del Código Sepsis', 'role_key' => 'Médicos de urgencias'],
            ['name' => 'Manejo del paciente séptico hospitalizado', 'role_key' => 'Médicos hospitalarios'],
            ['name' => 'Soporte hemodinámico avanzado en choque séptico', 'role_key' => 'Intensivistas'],
            ['name' => 'Bundle de primera hora y reevaluación clínica', 'role_key' => 'Enfermería'],
            ['name' => 'Toma y manejo de muestras (hemocultivos, lactato)', 'role_key' => 'Auxiliares de enfermería'],
            ['name' => 'Dispensación oportuna de antimicrobianos', 'role_key' => 'Farmacia'],
            ['name' => 'Ajuste y desescalamiento antimicrobiano', 'role_key' => 'Infectología'],
            ['name' => 'Procesamiento prioritario de muestras de sepsis', 'role_key' => 'Laboratorio'],
            ['name' => 'Reporte oportuno de microbiología', 'role_key' => 'Microbiología'],
            ['name' => 'Soporte ventilatorio en sepsis', 'role_key' => 'Terapia respiratoria'],
            ['name' => 'Control quirúrgico de la fuente', 'role_key' => 'Cirugía'],
            ['name' => 'Imágenes prioritarias para control del foco', 'role_key' => 'Radiología'],
            ['name' => 'Auditoría y análisis causal de casos de sepsis', 'role_key' => 'Calidad'],
            ['name' => 'Identificación y reporte de eventos de seguridad', 'role_key' => 'Seguridad del paciente'],
        ];

        foreach ($competencies as $competency) {
            Competency::query()->updateOrCreate(
                ['clinical_program_id' => $program->id, 'name' => $competency['name']],
                ['role_key' => $competency['role_key'], 'periodicity' => 'Anual'],
            );
        }
    }
}
