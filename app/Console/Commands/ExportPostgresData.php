<?php

namespace App\Console\Commands;

use App\Models\AccreditationChapter;
use App\Models\AccreditationFramework;
use App\Models\AccreditationStandard;
use App\Models\AcsCase;
use App\Models\AcsCaseAudit;
use App\Models\AcsComplication;
use App\Models\AcsDischargePlan;
use App\Models\AcsEcgRecord;
use App\Models\AcsFollowup;
use App\Models\AcsMedicationRecord;
use App\Models\AcsPciProcedure;
use App\Models\AcsRehabilitationReferral;
use App\Models\AcsTroponinRecord;
use App\Models\AcvCase;
use App\Models\AssessmentFinding;
use App\Models\CaseAudit;
use App\Models\CaseComment;
use App\Models\ClinicalAudit;
use App\Models\ClinicalProgram;
use App\Models\ClinicalRule;
use App\Models\CommitteeMeeting;
use App\Models\CommitteeMember;
use App\Models\ComplianceAssessment;
use App\Models\CorrectiveAction;
use App\Models\EvidenceDocument;
use App\Models\IndicatorTarget;
use App\Models\MeasurableElement;
use App\Models\MeetingAction;
use App\Models\MeetingDecision;
use App\Models\Patient;
use App\Models\ProgramCommittee;
use App\Models\ProgramMember;
use App\Models\ProgramResource;
use App\Models\SepsisAntimicrobialAdministration;
use App\Models\SepsisBundleTask;
use App\Models\SepsisCareTransition;
use App\Models\SepsisCase;
use App\Models\SepsisCulture;
use App\Models\SepsisHemodynamicAssessment;
use App\Models\SepsisPatientEducationRecord;
use App\Models\SepsisSafetyEvent;
use App\Models\SepsisScreening;
use App\Models\SepsisSourceControlAction;
use App\Models\Site;
use App\Models\TepAdvancedTherapy;
use App\Models\TepAnticoagulationEpisode;
use App\Models\TepCase;
use App\Models\TepCaseAudit;
use App\Models\TepCtepdEvaluation;
use App\Models\TepDischargePlan;
use App\Models\TepFollowup;
use App\Models\TepImagingStudy;
use App\Models\TepPertActivation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExportPostgresData extends Command
{
    protected $signature = 'agora:export-pg {out=storage/app/agora_pg_data.sql}';

    protected $description = 'Exporta los datos (data-only) en SQL compatible con PostgreSQL para cargar tras migrate.';

    /** Tablas en orden de dependencias (FK). Modelo null = tabla sin modelo Eloquent (ej. pivotes). */
    private const TABLES = [
        'users' => User::class,
        'patients' => Patient::class,
        'indicator_targets' => IndicatorTarget::class,
        'acv_cases' => AcvCase::class,
        'sepsis_cases' => SepsisCase::class,
        'case_comments' => CaseComment::class,
        'case_audits' => CaseAudit::class,
        // Programa / gobierno institucional
        'clinical_programs' => ClinicalProgram::class,
        'sites' => Site::class,
        'program_members' => ProgramMember::class,
        'clinical_rules' => ClinicalRule::class,
        'clinical_audits' => ClinicalAudit::class,
        'program_committees' => ProgramCommittee::class,
        'committee_members' => CommitteeMember::class,
        'committee_meetings' => CommitteeMeeting::class,
        'meeting_attendees' => null,
        'meeting_decisions' => MeetingDecision::class,
        'meeting_actions' => MeetingAction::class,
        // Acreditación / calidad
        'accreditation_frameworks' => AccreditationFramework::class,
        'accreditation_chapters' => AccreditationChapter::class,
        'accreditation_standards' => AccreditationStandard::class,
        'measurable_elements' => MeasurableElement::class,
        'compliance_assessments' => ComplianceAssessment::class,
        'evidence_documents' => EvidenceDocument::class,
        'evidence_document_measurable_element' => null,
        'assessment_findings' => AssessmentFinding::class,
        'corrective_actions' => CorrectiveAction::class,
        'program_resources' => ProgramResource::class,
        // Ruta clínica de síndrome coronario agudo
        'acs_cases' => AcsCase::class,
        'acs_ecg_records' => AcsEcgRecord::class,
        'acs_troponin_records' => AcsTroponinRecord::class,
        'acs_pci_procedures' => AcsPciProcedure::class,
        'acs_medication_records' => AcsMedicationRecord::class,
        'acs_complications' => AcsComplication::class,
        'acs_discharge_plans' => AcsDischargePlan::class,
        'acs_rehabilitation_referrals' => AcsRehabilitationReferral::class,
        'acs_followups' => AcsFollowup::class,
        'acs_case_audits' => AcsCaseAudit::class,
        // Ruta clínica de tromboembolismo pulmonar
        'tep_cases' => TepCase::class,
        'tep_pert_activations' => TepPertActivation::class,
        'tep_imaging_studies' => TepImagingStudy::class,
        'tep_anticoagulation_episodes' => TepAnticoagulationEpisode::class,
        'tep_advanced_therapies' => TepAdvancedTherapy::class,
        'tep_ivc_filters' => null,
        'tep_transfer_records' => null,
        'tep_discharge_plans' => TepDischargePlan::class,
        'tep_followups' => TepFollowup::class,
        'tep_ctepd_evaluations' => TepCtepdEvaluation::class,
        'tep_case_audits' => TepCaseAudit::class,
        // Ruta clínica de Sepsis
        'sepsis_screenings' => SepsisScreening::class,
        'sepsis_bundle_tasks' => SepsisBundleTask::class,
        'sepsis_hemodynamic_assessments' => SepsisHemodynamicAssessment::class,
        'sepsis_cultures' => SepsisCulture::class,
        'sepsis_antimicrobial_administrations' => SepsisAntimicrobialAdministration::class,
        'sepsis_source_control_actions' => SepsisSourceControlAction::class,
        'sepsis_care_transitions' => SepsisCareTransition::class,
        'sepsis_patient_education_records' => SepsisPatientEducationRecord::class,
        'sepsis_safety_events' => SepsisSafetyEvent::class,
    ];

    /** Tablas sin columna "id" (claves primarias compuestas) — no se reinicia secuencia. */
    private const TABLES_WITHOUT_ID = [
        'evidence_document_measurable_element',
    ];

    public function handle(): int
    {
        $path = base_path($this->argument('out'));
        $sql = "-- ÁGORA · datos para PostgreSQL (data-only). Cargar DESPUÉS de 'php artisan migrate'.\n";
        $sql .= "BEGIN;\n\n";

        foreach (self::TABLES as $table => $modelClass) {
            /** @var Model|null $model */
            $model = $modelClass ? new $modelClass : null;
            $casts = $model?->getCasts() ?? [];
            $rows = DB::table($table)->get();

            $sql .= "-- {$table} ({$rows->count()} filas)\n";

            foreach ($rows as $row) {
                $data = (array) $row;
                $columns = array_keys($data);
                $values = array_map(fn ($col) => $this->format($col, $data[$col], $casts), $columns);

                $cols = implode(', ', array_map(fn ($c) => "\"{$c}\"", $columns));
                $sql .= "INSERT INTO \"{$table}\" ({$cols}) VALUES (".implode(', ', $values).");\n";
            }

            if (! in_array($table, self::TABLES_WITHOUT_ID, true)) {
                // Reiniciar la secuencia del id.
                $sql .= "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), true);\n";
            }

            $sql .= "\n";
        }

        $sql .= "COMMIT;\n";

        file_put_contents($path, $sql);
        $this->info('Exportado a: '.$path.' ('.number_format(strlen($sql) / 1024, 0).' KB)');

        return self::SUCCESS;
    }

    private function format(string $col, mixed $value, array $casts): string
    {
        if ($value === null) {
            return 'NULL';
        }

        $cast = $casts[$col] ?? null;

        // Booleanos (SQLite los guarda como 0/1).
        if ($cast === 'boolean') {
            return $value ? 'true' : 'false';
        }

        // Numéricos.
        if (in_array($cast, ['integer', 'int', 'float', 'double'], true) || str_starts_with((string) $cast, 'decimal')) {
            return is_numeric($value) ? (string) $value : $this->quote((string) $value);
        }

        // Resto (texto, json, fechas, enums) → literal de texto escapado.
        return $this->quote((string) $value);
    }

    private function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
}
