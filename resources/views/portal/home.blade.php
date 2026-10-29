@extends('portal.layout')

@section('title', 'Mi recuperación')

@section('content')

@if (! $case)
    <div class="alert alert-warning">
        Todavía no tienes un programa de seguimiento activo. Cuando tu equipo te inscriba, aparecerá aquí.
    </div>
@else
    @php($g = $gamification)

    <div class="game-hero mb-4 game-pop">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <span class="game-mascot">🦸</span>
            </div>
            <div class="col">
                <h1 class="h3 mb-1">¡Hola{{ $actorFirstName ? ', '.$actorFirstName : '' }}! 👋</h1>
                <span class="game-speech">Cada cosita que registras hoy suma para tu recuperación. ¡Vamos por más! 💪</span>
                <div class="mt-2">
                    <span class="badge bg-white bg-opacity-75 text-dark">Caso {{ $case->case_number }}</span>
                    <span class="badge bg-white bg-opacity-75 text-dark">Etapa: {{ $case->clinicalStageLabel() }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($g)
        <div class="xp-card mb-4 game-pop">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="xp-level-badge">{{ $g['level'] }}</div>
                <div class="flex-grow-1" style="min-width: 220px;">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $g['levelTitle'] }}</strong>
                        <span class="text-muted small">{{ $g['xpIntoLevel'] }} / {{ $g['xpForNextLevel'] }} XP</span>
                    </div>
                    <div class="xp-bar-track mt-1">
                        <div class="xp-bar-fill" style="width: {{ $g['xpProgressPct'] }}%;"></div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="fs-4 fw-black" style="font-weight:900;">{{ $g['points'] }}</div>
                    <div class="text-muted small">puntos totales</div>
                </div>
                @if ($g['streakDays'] > 0)
                    <span class="streak-chip"><span class="streak-flame">🔥</span> {{ $g['streakDays'] }} día{{ $g['streakDays'] === 1 ? '' : 's' }} seguido{{ $g['streakDays'] === 1 ? '' : 's' }}</span>
                @endif
            </div>

            <div class="badge-shelf mt-3">
                @foreach ($g['badges'] as $badge)
                    <span class="badge-chip {{ $badge['unlocked'] ? '' : 'locked' }}" title="{{ $badge['unlocked'] ? '¡Insignia desbloqueada!' : 'Insignia bloqueada — sigue participando' }}">
                        <span class="badge-icon">{{ $badge['icon'] }}</span> {{ $badge['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($firstSteps && collect($firstSteps)->contains('done', false))
        <div class="feed-card mb-4">
            <h2 class="h6 mb-3">🚀 Primeros pasos</h2>
            @foreach ($firstSteps as $step)
                @if ($step['url'])
                    <a href="{{ $step['url'] }}" class="check-row {{ $step['done'] ? 'is-done' : '' }} game-pop text-decoration-none text-reset d-flex">
                        <div class="check-circle">{{ $step['done'] ? '✓' : $step['icon'] }}</div>
                        <div class="flex-grow-1 fw-semibold">{{ $step['title'] }}</div>
                    </a>
                @else
                    <div class="check-row {{ $step['done'] ? 'is-done' : '' }} d-flex">
                        <div class="check-circle">{{ $step['done'] ? '✓' : $step['icon'] }}</div>
                        <div class="flex-grow-1 fw-semibold">{{ $step['title'] }}</div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if ($dailyTip)
        <div class="feed-card mb-4 game-pop" style="background: linear-gradient(135deg,#fff7ed,#fffbeb); border-color:#fde68a;">
            <div class="small text-muted mb-1">✨ Consejo del día</div>
            <div class="fw-semibold">{{ $dailyTip }}</div>
        </div>
    @endif

    @php($pendingCount = $unreadEducation + $pendingReadinessItems + $openSupportRequests + ($pendingJourneySteps ?? 0))
    @if ($pendingCount > 0)
        <div class="alert alert-info mb-4">
            <strong>🔔 Tienes {{ $pendingCount }} pendiente{{ $pendingCount === 1 ? '' : 's' }}:</strong>
            <ul class="mb-0 mt-1">
                @if ($unreadEducation > 0)
                    <li><a href="{{ route('portal.education') }}">{{ $unreadEducation }} contenido{{ $unreadEducation === 1 ? '' : 's' }} educativo{{ $unreadEducation === 1 ? '' : 's' }} sin leer</a></li>
                @endif
                @if ($pendingReadinessItems > 0)
                    <li><a href="{{ route('portal.discharge-readiness') }}">{{ $pendingReadinessItems }} tema{{ $pendingReadinessItems === 1 ? '' : 's' }} de preparación para el alta sin revisar</a></li>
                @endif
                @if ($openSupportRequests > 0)
                    <li><a href="{{ route('portal.support') }}">{{ $openSupportRequests }} solicitud{{ $openSupportRequests === 1 ? '' : 'es' }} esperando respuesta</a></li>
                @endif
                @if (($pendingJourneySteps ?? 0) > 0)
                    <li><a href="{{ route('portal.caregiver-journey') }}">{{ $pendingJourneySteps }} paso{{ $pendingJourneySteps === 1 ? '' : 's' }} de tu ruta como cuidador sin completar</a></li>
                @endif
            </ul>
        </div>
    @endif

    @if ($ritual)
        <h2 class="h5 mb-3">☀️ Tu día</h2>
        <p class="text-muted small mb-3">Un vistazo rápido a lo de hoy — a tu ritmo, no tiene que ser exactamente en este orden ni a esta hora.</p>

        <div class="row g-3 mb-4">
            @foreach ($ritual as $block)
                <div class="col-md-4">
                    <div class="feed-card h-100">
                        <h3 class="h6 mb-3">{{ $block['icon'] }} {{ $block['label'] }}</h3>

                        @foreach ($block['checklist'] as $item)
                            <a href="{{ $item['url'] }}" class="check-row {{ $item['done'] ? 'is-done' : '' }} game-pop text-decoration-none text-reset d-flex">
                                <div class="check-circle">{{ $item['done'] ? '✓' : $item['icon'] }}</div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $item['title'] }}</div>
                                    @if (! $item['done'])
                                        <div class="text-muted small">Toca para hacerlo ahora</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach

                        @if (count($block['agenda']) > 0)
                            <hr class="my-3">
                            <div class="small text-muted mb-2">Agendado para hoy:</div>
                            @foreach ($block['agenda'] as $entry)
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-light text-dark">{{ $entry['time'] }}</span>
                                    <span>{{ $entry['icon'] }} {{ $entry['label'] }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="h5 mb-3">🗺️ Explora todo</h2>
    <div class="mission-grid">
        @foreach ($missions as $mission)
            <a href="{{ $mission['url'] }}" class="mission-card game-pop">
                @if (($mission['pending'] ?? 0) > 0)
                    <span class="mission-pending-dot">{{ $mission['pending'] }}</span>
                @endif
                <span class="mission-icon" style="background: {{ $mission['color'] }};">{{ $mission['icon'] }}</span>
                <div class="mission-title">{{ $mission['title'] }}</div>
                <div class="mission-desc">{{ $mission['description'] }}</div>
            </a>
        @endforeach
    </div>

    @if ($showTour)
        <div class="modal fade" id="portalTourModal" tabindex="-1" aria-labelledby="portalTourModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('portal.tour.dismiss') }}" class="modal-content" style="border-radius: 1.5rem; overflow: hidden;">
                    @csrf
                    <div class="modal-header border-0" style="background: linear-gradient(135deg,#7c3aed,#ec4899); color: #fff;">
                        <h5 class="modal-title" id="portalTourModalLabel">🎮 ¡Bienvenido a tu aventura de recuperación!</h5>
                        <button type="submit" class="btn-close btn-close-white" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p>Para animarte a participar, esta pantalla funciona un poco como un juego:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">⭐ <strong>Puntos:</strong> cada vez que registras algo (tu diario, un avance, cómo te sientes...) ganas puntos y subes de nivel.</li>
                            <li class="mb-2">🏅 <strong>Insignias:</strong> se desbloquean la primera vez que haces cada tipo de actividad.</li>
                            <li class="mb-2">🔥 <strong>Racha:</strong> cuenta los días seguidos que participas.</li>
                        </ul>
                        <p class="text-muted small mb-0">Esto es solo para animarte — no es parte de tu evaluación médica. Tu equipo solo ve la información real que reportas, nunca tus puntos.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-game">¡Entendido, vamos! 🚀</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('portalTourModal');
                if (el && window.bootstrap) {
                    new bootstrap.Modal(el).show();
                }
            });
        </script>
    @endif
@endif
@endsection
