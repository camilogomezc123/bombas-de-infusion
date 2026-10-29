<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Hub;
use App\Filament\Pics\Pages\CoordinatedAgenda;
use App\Filament\Pics\Pages\ExecutiveSummary;
use App\Filament\Pics\Pages\PendingReviews;
use App\Filament\Pics\Pages\PicsIndicators;
use App\Filament\Pics\Pages\PortalEngagement;
use App\Filament\Pics\Resources\CarePlans\CarePlanResource;
use App\Filament\Pics\Resources\DischargeReadinessChecks\DischargeReadinessCheckResource;
use App\Filament\Pics\Resources\EducationResources\EducationResourceResource;
use App\Filament\Pics\Resources\MedicationReconciliations\MedicationReconciliationResource;
use App\Filament\Pics\Resources\PicsCases\PicsCaseResource;
use App\Filament\Pics\Resources\RecoveryGoals\RecoveryGoalResource;
use App\Filament\Pics\Resources\RecoveryPassports\RecoveryPassportResource;
use App\Filament\Pics\Resources\SupportRequests\SupportRequestResource;
use App\Filament\Programs\Shared\Resources\ClinicalRules\ClinicalRuleResource;
use App\Filament\Sepsis\Resources\ClinicalPrograms\ClinicalProgramResource;
use App\Filament\Sepsis\Resources\Competencies\CompetencyResource;
use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use App\Filament\Sepsis\Resources\Findings\FindingResource;
use App\Filament\Sepsis\Resources\IndicatorDefinitions\IndicatorDefinitionResource;
use App\Filament\Sepsis\Resources\ProgramCommittees\ProgramCommitteeResource;
use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
use App\Filament\Sepsis\Resources\ProgramMemberships\ProgramMembershipResource;
use App\Filament\Sepsis\Resources\ProgramResources\ProgramResourceResource;
use App\Filament\Sepsis\Resources\ProtocolGaps\ProtocolGapResource;
use App\Filament\Sepsis\Resources\QualityStandards\QualityStandardResource;
use App\Filament\Sepsis\Resources\RaciAssignments\RaciAssignmentResource;
use App\Support\ExcellenceCenters;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PicsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $shared = [ClinicalProgramResource::class, ProgramMembershipResource::class, CompetencyResource::class,
            ProgramCommitteeResource::class, RaciAssignmentResource::class, ProgramResourceResource::class,
            ProgramDocumentResource::class, ProtocolGapResource::class, ClinicalRuleResource::class,
            QualityStandardResource::class, ComplianceEvidenceResource::class, FindingResource::class,
            IndicatorDefinitionResource::class];

        return $panel->id('pics')->path('pics')->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)->passwordReset()->profile(EditProfile::class)
            ->brandName('PICS · Seguimiento Post-UCI')->brandLogo(asset('img/LOGO1.png'))->brandLogoHeight('2.75rem')
            ->favicon(asset('img/favicon.png'))->font('Montserrat')->topNavigation()
            ->navigation(fn (NavigationBuilder $builder) => $builder
                ->group(NavigationGroup::make()->items([...ExecutiveSummary::getNavigationItems()]))
                ->group(NavigationGroup::make('Programa PICS')->items([...PicsCaseResource::getNavigationItems(), ...RecoveryPassportResource::getNavigationItems(), ...RecoveryGoalResource::getNavigationItems(), ...SupportRequestResource::getNavigationItems(), ...CarePlanResource::getNavigationItems(), ...DischargeReadinessCheckResource::getNavigationItems(), ...MedicationReconciliationResource::getNavigationItems(), ...EducationResourceResource::getNavigationItems(), ...PendingReviews::getNavigationItems(), ...PortalEngagement::getNavigationItems(), ...CoordinatedAgenda::getNavigationItems()]))
                ->group(NavigationGroup::make('Gobierno clínico')->items([...ClinicalProgramResource::getNavigationItems(), ...ProgramMembershipResource::getNavigationItems(), ...CompetencyResource::getNavigationItems(), ...ProgramCommitteeResource::getNavigationItems(), ...RaciAssignmentResource::getNavigationItems(), ...ProgramResourceResource::getNavigationItems(), ...ProgramDocumentResource::getNavigationItems(), ...ProtocolGapResource::getNavigationItems(), ...ClinicalRuleResource::getNavigationItems()]))
                ->group(NavigationGroup::make('Calidad y mejora')->items([...QualityStandardResource::getNavigationItems(), ...ComplianceEvidenceResource::getNavigationItems(), ...FindingResource::getNavigationItems()]))
                ->group(NavigationGroup::make()->items([...PicsIndicators::getNavigationItems(), ...IndicatorDefinitionResource::getNavigationItems()])))
            ->homeUrl(fn (): string => Hub::getUrl(panel: 'admin'))->globalSearch(false)
            ->databaseNotifications()->databaseNotificationsPolling('15s')
            ->colors(['primary' => Color::Teal, 'gray' => Color::Slate])
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, fn (): string => ExcellenceCenters::headerBadge('PICS'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn (): string => view('filament.components.centers-menu')->render())
            ->resources([PicsCaseResource::class, RecoveryPassportResource::class, RecoveryGoalResource::class, SupportRequestResource::class, CarePlanResource::class, DischargeReadinessCheckResource::class, MedicationReconciliationResource::class, EducationResourceResource::class, ...$shared])
            ->pages([ExecutiveSummary::class, PicsIndicators::class, PendingReviews::class, PortalEngagement::class, CoordinatedAgenda::class])->widgets([])
            ->middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, AuthenticateSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class])
            ->authMiddleware([Authenticate::class]);
    }
}
