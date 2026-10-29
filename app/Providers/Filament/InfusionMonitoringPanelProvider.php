<?php

namespace App\Providers\Filament;

use App\Filament\InfusionMonitoring\Pages\AlarmInsights;
use App\Filament\InfusionMonitoring\Pages\InfusionIndicators;
use App\Filament\InfusionMonitoring\Pages\MonitoringWall;
use App\Filament\InfusionMonitoring\Pages\OverrideAudit;
use App\Filament\InfusionMonitoring\Pages\PumpCensus;
use App\Filament\InfusionMonitoring\Pages\ShiftHandoffReport;
use App\Filament\InfusionMonitoring\Pages\StayInfusionTimeline;
use App\Filament\InfusionMonitoring\Pages\UnitTrends;
use App\Filament\InfusionMonitoring\Resources\DrugInfusionProfiles\DrugInfusionProfileResource;
use App\Filament\InfusionMonitoring\Resources\InfusionClinicalAlerts\InfusionClinicalAlertResource;
use App\Filament\InfusionMonitoring\Resources\InfusionEvents\InfusionEventResource;
use App\Filament\InfusionMonitoring\Resources\PumpDevices\PumpDeviceResource;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Hub;
use App\Filament\Sepsis\Resources\ProgramMemberships\ProgramMembershipResource;
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

class InfusionMonitoringPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('infusion-monitoring')->path('infusion-monitoring')->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)->passwordReset()->profile(EditProfile::class)
            ->brandName('ÁGORA · Monitoreo de Bombas')->brandLogo(asset('img/LOGO1.png'))->brandLogoHeight('2.75rem')
            ->favicon(asset('img/favicon.png'))->font('Montserrat')->topNavigation()
            ->navigation(fn (NavigationBuilder $builder) => $builder
                ->group(NavigationGroup::make()->items([...MonitoringWall::getNavigationItems(), ...PumpCensus::getNavigationItems(), ...StayInfusionTimeline::getNavigationItems()]))
                ->group(NavigationGroup::make('Clínico')->items([...InfusionEventResource::getNavigationItems(), ...InfusionClinicalAlertResource::getNavigationItems(), ...AlarmInsights::getNavigationItems(), ...ShiftHandoffReport::getNavigationItems()]))
                ->group(NavigationGroup::make('Configuración')->items([...PumpDeviceResource::getNavigationItems(), ...DrugInfusionProfileResource::getNavigationItems()]))
                ->group(NavigationGroup::make('Gobierno')->items([...ProgramMembershipResource::getNavigationItems(), ...InfusionIndicators::getNavigationItems(), ...OverrideAudit::getNavigationItems(), ...UnitTrends::getNavigationItems()])))
            ->homeUrl(fn (): string => Hub::getUrl(panel: 'admin'))->globalSearch(false)
            ->databaseNotifications()->databaseNotificationsPolling('15s')
            ->colors(['primary' => Color::Rose, 'gray' => Color::Slate])
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, fn (): string => ExcellenceCenters::headerBadge('Monitoreo de Bombas'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn (): string => view('filament.components.centers-menu')->render())
            ->resources([PumpDeviceResource::class, DrugInfusionProfileResource::class, InfusionEventResource::class, InfusionClinicalAlertResource::class, ProgramMembershipResource::class])
            ->pages([MonitoringWall::class, PumpCensus::class, StayInfusionTimeline::class, InfusionIndicators::class, AlarmInsights::class, OverrideAudit::class, UnitTrends::class, ShiftHandoffReport::class])->widgets([])
            ->middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, AuthenticateSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class])
            ->authMiddleware([Authenticate::class]);
    }
}
