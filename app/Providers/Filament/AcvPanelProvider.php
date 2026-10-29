<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Hub;
use App\Support\ExcellenceCenters;
use Filament\View\PanelsRenderHook;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel del Centro de Excelencia ACV (/acv).
 * Solo descubre los recursos y páginas de ACV: ningún otro centro puede ver esta base.
 */
class AcvPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('acv')
            ->path('acv')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditProfile::class)
            ->brandName('ÁGORA · ACV')
            ->brandLogo(asset('img/LOGO1.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('img/favicon.png'))
            ->font('Montserrat')
            ->topNavigation()
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
                fn (): string => ExcellenceCenters::headerBadge('ACV'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.components.centers-menu')->render(),
            )
            ->discoverResources(in: app_path('Filament/Acv/Resources'), for: 'App\Filament\Acv\Resources')
            ->discoverPages(in: app_path('Filament/Acv/Pages'), for: 'App\Filament\Acv\Pages')
            ->discoverWidgets(in: app_path('Filament/Acv/Widgets'), for: 'App\Filament\Acv\Widgets')
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
