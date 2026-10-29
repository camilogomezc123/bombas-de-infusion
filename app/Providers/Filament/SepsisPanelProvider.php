<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Hub;
use App\Filament\Programs\Shared\Resources\ClinicalRules\ClinicalRuleResource;
use App\Filament\Sepsis\Pages\ClinicalPathway;
use App\Filament\Sepsis\Pages\ExecutiveSummary;
use App\Filament\Sepsis\Pages\MonthlyReport;
use App\Filament\Sepsis\Pages\SepsisIndicators;
use App\Filament\Sepsis\Pages\Tracers;
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
use App\Filament\Sepsis\Resources\SafetyEvents\SafetyEventResource;
use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Filament\Sepsis\Resources\Sites\SiteResource;
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

/**
 * Panel del Centro de Excelencia de Sepsis (/sepsis).
 * Solo descubre los recursos y páginas de Sepsis; aislado de los demás centros.
 */
class SepsisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sepsis')
            ->path('sepsis')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditProfile::class)
            ->brandName('ÁGORA · Sepsis')
            ->brandLogo(asset('img/LOGO1.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('img/favicon.png'))
            ->font('Montserrat')
            ->topNavigation()
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $builder
                ->group(NavigationGroup::make()->items([
                    ...ExecutiveSummary::getNavigationItems(),
                    ...SepsisCaseResource::getNavigationItems(),
                    ...ClinicalPathway::getNavigationItems(),
                ]))
                ->group(NavigationGroup::make('Programa')->items([
                    ...ClinicalProgramResource::getNavigationItems(),
                    ...SiteResource::getNavigationItems(),
                    ...ProgramMembershipResource::getNavigationItems(),
                    ...CompetencyResource::getNavigationItems(),
                    ...ProgramCommitteeResource::getNavigationItems(),
                    ...RaciAssignmentResource::getNavigationItems(),
                    ...ProgramResourceResource::getNavigationItems(),
                    ...ProgramDocumentResource::getNavigationItems(),
                    ...ProtocolGapResource::getNavigationItems(),
                    ...ClinicalRuleResource::getNavigationItems(),
                ]))
                ->group(NavigationGroup::make('Calidad y mejora')->items([
                    ...QualityStandardResource::getNavigationItems(),
                    ...ComplianceEvidenceResource::getNavigationItems(),
                    ...SafetyEventResource::getNavigationItems(),
                    ...FindingResource::getNavigationItems(),
                ]))
                ->group(NavigationGroup::make()->items([
                    ...SepsisIndicators::getNavigationItems(),
                    ...IndicatorDefinitionResource::getNavigationItems(),
                    ...MonthlyReport::getNavigationItems(),
                    ...Tracers::getNavigationItems(),
                ])))
            ->homeUrl(fn (): string => Hub::getUrl(panel: 'admin'))
            ->globalSearch(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->colors([
                'primary' => Color::hex('#17375e'),
                'gray' => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => ExcellenceCenters::headerBadge('Sepsis'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.components.centers-menu')->render(),
            )
            ->discoverResources(in: app_path('Filament/Sepsis/Resources'), for: 'App\Filament\Sepsis\Resources')
            ->resources([ClinicalRuleResource::class])
            ->discoverPages(in: app_path('Filament/Sepsis/Pages'), for: 'App\Filament\Sepsis\Pages')
            ->discoverWidgets(in: app_path('Filament/Sepsis/Widgets'), for: 'App\Filament\Sepsis\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
