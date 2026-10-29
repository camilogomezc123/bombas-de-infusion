<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#006a63">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>@yield('title', 'Mi recuperación') · POSUCI 360 Conecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/portal-game.css') }}">
    <link rel="stylesheet" href="{{ asset('css/portal-theme.css') }}">
</head>
@php
    $portalActor = auth('patient')->user() ?? auth('caregiver')->user();
    $actorName = $portalActor ? ($portalActor->full_name ?? $portalActor->name) : null;
    $actorRole = auth('patient')->check() ? 'Paciente' : 'Familiar / cuidador';
    $actorEasyMode = (bool) ($portalActor?->portal_easy_mode ?? false);
    $caregiverCase = auth('caregiver')->check() ? \App\Http\Controllers\Portal\PortalHomeController::currentCase() : null;
    $canAccessCaregiverJourney = $caregiverCase && \App\Support\Posuci\CaseAccess::caregiverCanAccessJourney(auth('caregiver')->user(), $caregiverCase);
    $navModules = collect([
        ['icon' => 'home', 'label' => 'Mi recuperación', 'route' => 'portal.home', 'group' => 'home'],
        ['icon' => 'calendar', 'label' => 'Mi calendario', 'route' => 'portal.calendar', 'group' => 'plan'],
        ['icon' => 'book', 'label' => 'Antes y ahora', 'route' => 'portal.passport', 'group' => 'profile'],
        ['icon' => 'book', 'label' => 'Mi diario', 'route' => 'portal.diary', 'group' => 'plan'],
        ['icon' => 'plan', 'label' => 'Mis metas', 'route' => 'portal.goals', 'group' => 'plan'],
        ['icon' => 'heart', 'label' => 'Cómo me siento', 'route' => 'portal.wellbeing', 'group' => 'plan'],
        ['icon' => 'team', 'label' => 'Necesito ayuda', 'route' => 'portal.support', 'group' => 'team'],
        ['icon' => 'shield', 'label' => 'Medicamentos', 'route' => 'portal.medications', 'group' => 'plan'],
        ['icon' => 'heart', 'label' => 'Monitoreo en casa', 'route' => 'portal.home-monitoring', 'group' => 'plan'],
        ['icon' => 'progress', 'label' => 'Mi progreso', 'route' => 'portal.progress', 'group' => 'progress'],
        ['icon' => 'book', 'label' => 'Educación', 'route' => 'portal.education', 'group' => 'plan'],
        ['icon' => 'shield', 'label' => 'Preparación para el alta', 'route' => 'portal.discharge-readiness', 'group' => 'plan'],
        ['icon' => 'plan', 'label' => 'Resumen para tu cita', 'route' => 'portal.summary', 'group' => 'progress'],
        ['icon' => 'profile', 'label' => 'Mi perfil y familia', 'route' => 'portal.profile', 'group' => 'profile'],
    ]);
    if ($canAccessCaregiverJourney) {
        $navModules->push(['icon' => 'team', 'label' => 'Mi ruta como cuidador', 'route' => 'portal.caregiver-journey', 'group' => 'profile']);
    }
    $activeGroup = $navModules->first(fn ($module) => request()->routeIs($module['route']))['group'] ?? 'home';
    $primaryNav = [
        ['group' => 'home', 'label' => 'Inicio', 'route' => 'portal.home', 'icon' => 'home'],
        ['group' => 'plan', 'label' => 'Mi plan', 'route' => 'portal.goals', 'icon' => 'plan'],
        ['group' => 'progress', 'label' => 'Progreso', 'route' => 'portal.progress', 'icon' => 'progress'],
        ['group' => 'team', 'label' => 'Equipo', 'route' => 'portal.support', 'icon' => 'team'],
        ['group' => 'profile', 'label' => 'Perfil', 'route' => 'portal.profile', 'icon' => 'profile'],
    ];
@endphp
<body class="game-mode {{ $actorEasyMode ? 'easy-mode' : '' }}">
    <a href="#portal-main" class="portal-skip-link">Saltar al contenido</a>
    <header class="portal-header posuci-navbar">
        <div class="portal-header-inner container">
            <a class="portal-brand" href="{{ route('portal.home') }}" aria-label="POSUCI 360 Conecta, inicio">
                <span class="portal-brand-mark"><x-portal.icon name="heart" /></span>
                <span><strong>POSUCI 360 <span class="portal-brand-extra">Conecta</span></strong><small>Tu recuperación, acompañada</small></span>
            </a>
            @if ($actorName)
                <div class="portal-header-actions">
                    <span class="portal-account"><strong>{{ $actorName }}</strong><small>{{ $actorRole }}</small></span>
                    @livewire('portal.notification-center-component')
                    <div class="dropdown">
                        <button class="btn portal-settings" type="button" data-bs-toggle="dropdown" aria-expanded="false">⚙️ Ajustes</button>
                        <div class="dropdown-menu dropdown-menu-end p-2 portal-settings-menu">
                            <a href="{{ route('portal.profile') }}" class="dropdown-item rounded-3 mb-1">Mi perfil y familia</a>
                            <form method="POST" action="{{ route('portal.easy-mode.toggle') }}">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-3 mb-1">{{ $actorEasyMode ? '🔎 Modo fácil: activado ✓' : '🔎 Activar modo fácil' }}</button>
                            </form>
                            <button type="button" id="pushToggleBtn" class="dropdown-item rounded-3 mb-1" style="display:none;"
                                data-key-url="{{ route('portal.push.public-key') }}"
                                data-subscribe-url="{{ route('portal.push.subscribe') }}"
                                data-unsubscribe-url="{{ route('portal.push.unsubscribe') }}"
                                data-csrf="{{ csrf_token() }}">🔔 Activar notificaciones</button>
                            <button type="button" id="installAppBtn" class="dropdown-item rounded-3 mb-1" style="display:none;">📲 Instalar app</button>
                            <hr class="dropdown-divider my-1">
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-3 text-danger">🚪 Salir</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @if ($actorName)
            <div class="portal-nav-wrap">
                <div class="container portal-nav-inner">
                    <nav class="portal-primary-nav" aria-label="Navegación principal">
                        @foreach ($primaryNav as $item)
                            <a href="{{ route($item['route']) }}" @if($activeGroup === $item['group']) aria-current="page" @endif><x-portal.icon :name="$item['icon']" />{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                    <button type="button" class="btn portal-all-modules" data-bs-toggle="offcanvas" data-bs-target="#navModulesOffcanvas" aria-controls="navModulesOffcanvas"><x-portal.icon name="grid" /> Todos los módulos</button>
                </div>
            </div>
        @endif
    </header>

    <main id="portal-main" class="container portal-main" tabindex="-1">
        @if ($actorName && $activeGroup !== 'home')
            <nav class="portal-section-nav" aria-label="Secciones de {{ collect($primaryNav)->firstWhere('group', $activeGroup)['label'] }}">
                @foreach ($navModules->where('group', $activeGroup) as $module)
                    <a href="{{ route($module['route']) }}" @if(request()->routeIs($module['route'])) aria-current="page" @endif>{{ $module['label'] }}</a>
                @endforeach
            </nav>
        @endif
        @if (session('diary_status'))
            <div class="alert alert-success" role="status">{{ session('diary_status') }}</div>
        @endif
        @if (session('goals_status'))
            <div class="alert alert-success" role="status">{{ session('goals_status') }}</div>
        @endif
        @yield('content')
    </main>

    @if ($actorName)
        <nav class="portal-bottom-nav" aria-label="Navegación móvil">
            @foreach ($primaryNav as $item)
                <a href="{{ route($item['route']) }}" @if($activeGroup === $item['group']) aria-current="page" @endif><x-portal.icon :name="$item['icon']" /><span>{{ $item['label'] }}</span></a>
            @endforeach
        </nav>
        <div class="portal-utilities">
            <button type="button" class="btn btn-game-outline bg-white" data-bs-toggle="offcanvas" data-bs-target="#navModulesOffcanvas" aria-controls="navModulesOffcanvas">🗺️ Módulos</button>
            <button type="button" id="readAloudBtn" class="btn btn-game" style="display:none;">🔊 Leer esta página</button>
        </div>
        <div class="offcanvas offcanvas-bottom portal-modules-drawer" tabindex="-1" id="navModulesOffcanvas" aria-labelledby="navModulesOffcanvasLabel">
            <div class="offcanvas-header">
                <h2 class="h5 offcanvas-title" id="navModulesOffcanvasLabel">Todos los módulos</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            </div>
            <div class="offcanvas-body">
                <div class="mission-grid">
                    @foreach ($navModules as $module)
                        <a href="{{ route($module['route']) }}" class="mission-card" @if(request()->routeIs($module['route'])) aria-current="page" @endif>
                            <span class="mission-icon"><x-portal.icon :name="$module['icon']" /></span>
                            <div class="mission-title">{{ $module['label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="{{ asset('js/portal-game.js') }}"></script>
    <script src="{{ asset('js/portal-push.js') }}"></script>
    <script src="{{ asset('js/portal-install.js') }}"></script>
    <script src="{{ asset('js/portal-voice.js') }}"></script>
</body>
</html>
