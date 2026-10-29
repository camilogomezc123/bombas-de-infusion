<?php

namespace App\Providers;

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
use App\Models\CarePlan;
use App\Models\Caregiver;
use App\Models\CaregiverJourneyStep;
use App\Models\CaseComment;
use App\Models\CommitteeMeeting;
use App\Models\Competency;
use App\Models\DischargeReadinessCheck;
use App\Models\EvidenceAcknowledgement;
use App\Models\EvidenceDocument;
use App\Models\EducationAssignment;
use App\Models\HomeMonitoringReading;
use App\Models\IndicatorDefinition;
use App\Models\InfusionClinicalAlert;
use App\Models\MedicationReconciliation;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Models\PicsFollowup;
use App\Models\PicsReferral;
use App\Models\Patient;
use App\Models\ProgramMember;
use App\Models\ProgramResource;
use App\Models\ProtocolGap;
use App\Models\RaciAssignment;
use App\Models\RecoveryPassport;
use App\Models\SepsisAntimicrobialAdministration;
use App\Models\SepsisBundleTask;
use App\Models\SepsisCareTransition;
use App\Models\SepsisCase;
use App\Models\SepsisCulture;
use App\Models\SepsisHemodynamicAssessment;
use App\Models\SepsisPatientEducationRecord;
use App\Models\SepsisPostsepsisFollowup;
use App\Models\SepsisSafetyEvent;
use App\Models\SepsisScreening;
use App\Models\SepsisSourceControlAction;
use App\Models\Site;
use App\Models\StaffCompetency;
use App\Models\SupportRequest;
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
use App\Observers\AcvCaseObserver;
use App\Observers\CarePlanObserver;
use App\Observers\CaseCommentObserver;
use App\Observers\IndicatorDefinitionObserver;
use App\Observers\PicsCaseObserver;
use App\Observers\ProgramClinicalRecordObserver;
use App\Observers\SepsisCaseObserver;
use App\Observers\SepsisChildRecordObserver;
use App\Observers\InfusionClinicalAlertObserver;
use App\Observers\SupportRequestObserver;
use App\Services\Infusion\Connectors\BdHealthSightConnector;
use App\Services\Infusion\Connectors\FixturePumpConnector;
use App\Services\Infusion\Contracts\PumpDataConnector;
use App\Policies\CommitteeMeetingPolicy;
use App\Policies\CompetencyPolicy;
use App\Policies\ComplianceEvidencePolicy;
use App\Policies\EvidenceAcknowledgementPolicy;
use App\Policies\FindingPolicy;
use App\Policies\IndicatorDefinitionPolicy;
use App\Policies\PicsFollowupPolicy;
use App\Policies\ProgramMembershipPolicy;
use App\Policies\ProgramResourcePolicy;
use App\Policies\ProtocolGapPolicy;
use App\Policies\QualityStandardPolicy;
use App\Policies\RaciAssignmentPolicy;
use App\Policies\SafetyEventPolicy;
use App\Policies\SepsisPostsepsisFollowupPolicy;
use App\Policies\SitePolicy;
use App\Policies\StaffCompetencyPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PumpDataConnector::class, function () {
            return match (config('services.infusion_pumps.driver')) {
                'bd_healthsight' => $this->app->make(BdHealthSightConnector::class),
                default => $this->app->make(FixturePumpConnector::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AcvCase::observe(AcvCaseObserver::class);
        SepsisCase::observe(SepsisCaseObserver::class);
        AcsCase::observe(ProgramClinicalRecordObserver::class);
        AcsEcgRecord::observe(ProgramClinicalRecordObserver::class);
        AcsTroponinRecord::observe(ProgramClinicalRecordObserver::class);
        AcsPciProcedure::observe(ProgramClinicalRecordObserver::class);
        AcsMedicationRecord::observe(ProgramClinicalRecordObserver::class);
        AcsComplication::observe(ProgramClinicalRecordObserver::class);
        AcsDischargePlan::observe(ProgramClinicalRecordObserver::class);
        AcsRehabilitationReferral::observe(ProgramClinicalRecordObserver::class);
        AcsFollowup::observe(ProgramClinicalRecordObserver::class);
        AcsCaseAudit::observe(ProgramClinicalRecordObserver::class);
        TepCase::observe(ProgramClinicalRecordObserver::class);
        TepPertActivation::observe(ProgramClinicalRecordObserver::class);
        TepImagingStudy::observe(ProgramClinicalRecordObserver::class);
        TepAnticoagulationEpisode::observe(ProgramClinicalRecordObserver::class);
        TepAdvancedTherapy::observe(ProgramClinicalRecordObserver::class);
        TepDischargePlan::observe(ProgramClinicalRecordObserver::class);
        TepFollowup::observe(ProgramClinicalRecordObserver::class);
        TepCtepdEvaluation::observe(ProgramClinicalRecordObserver::class);
        TepCaseAudit::observe(ProgramClinicalRecordObserver::class);
        CaseComment::observe(CaseCommentObserver::class);
        SepsisScreening::observe(SepsisChildRecordObserver::class);
        SepsisBundleTask::observe(SepsisChildRecordObserver::class);
        SepsisHemodynamicAssessment::observe(SepsisChildRecordObserver::class);
        SepsisCulture::observe(SepsisChildRecordObserver::class);
        SepsisAntimicrobialAdministration::observe(SepsisChildRecordObserver::class);
        SepsisSourceControlAction::observe(SepsisChildRecordObserver::class);
        SepsisCareTransition::observe(SepsisChildRecordObserver::class);
        SepsisPatientEducationRecord::observe(SepsisChildRecordObserver::class);
        SepsisPostsepsisFollowup::observe(SepsisChildRecordObserver::class);
        PicsCase::observe(PicsCaseObserver::class);
        PicsFollowup::observe(SepsisChildRecordObserver::class);
        PicsReferral::observe(SepsisChildRecordObserver::class);
        RecoveryPassport::observe(SepsisChildRecordObserver::class);
        SupportRequest::observe(SepsisChildRecordObserver::class);
        SupportRequest::observe(SupportRequestObserver::class);
        CarePlan::observe(CarePlanObserver::class);
        CaregiverJourneyStep::observe(SepsisChildRecordObserver::class);
        DischargeReadinessCheck::observe(SepsisChildRecordObserver::class);
        PicsAgendaItem::observe(SepsisChildRecordObserver::class);
        MedicationReconciliation::observe(SepsisChildRecordObserver::class);
        HomeMonitoringReading::observe(SepsisChildRecordObserver::class);
        EducationAssignment::observe(SepsisChildRecordObserver::class);
        IndicatorDefinition::observe(IndicatorDefinitionObserver::class);
        InfusionClinicalAlert::observe(InfusionClinicalAlertObserver::class);
        Gate::policy(AccreditationStandard::class, QualityStandardPolicy::class);
        Gate::policy(EvidenceDocument::class, ComplianceEvidencePolicy::class);
        Gate::policy(EvidenceAcknowledgement::class, EvidenceAcknowledgementPolicy::class);
        Gate::policy(ProgramMember::class, ProgramMembershipPolicy::class);
        Gate::policy(ProgramResource::class, ProgramResourcePolicy::class);
        Gate::policy(SepsisSafetyEvent::class, SafetyEventPolicy::class);
        Gate::policy(AssessmentFinding::class, FindingPolicy::class);
        Gate::policy(CommitteeMeeting::class, CommitteeMeetingPolicy::class);
        Gate::policy(ProtocolGap::class, ProtocolGapPolicy::class);
        Gate::policy(RaciAssignment::class, RaciAssignmentPolicy::class);
        Gate::policy(IndicatorDefinition::class, IndicatorDefinitionPolicy::class);
        Gate::policy(Competency::class, CompetencyPolicy::class);
        Gate::policy(StaffCompetency::class, StaffCompetencyPolicy::class);
        Gate::policy(SepsisPostsepsisFollowup::class, SepsisPostsepsisFollowupPolicy::class);
        Gate::policy(PicsFollowup::class, PicsFollowupPolicy::class);
        Gate::policy(Site::class, SitePolicy::class);

        // Registrar el último acceso en cada inicio de sesión real — staff (guard "web")
        // y también paciente/cuidador (guards "patient"/"caregiver" del portal), que es
        // la base de la trazabilidad de uso del portal en /pics/trazabilidad-portal.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User || $event->user instanceof Patient || $event->user instanceof Caregiver) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
