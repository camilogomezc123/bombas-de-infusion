@extends('portal.layout')

@section('title', 'Mi progreso')

@section('content')

@if (! $case)
    <div class="alert alert-warning">Todavía no tienes un programa de seguimiento activo.</div>
@else
    <h1 class="h3 mb-1">📈 Mi progreso</h1>
    <p class="text-muted mb-4">Cómo ha ido cambiando lo que has registrado en el tiempo.</p>

    @if ($wellbeingChart)
        <div class="feed-card mb-4">
            <h2 class="h6 mb-3">💙 Bienestar</h2>
            <canvas id="wellbeingChart" height="90"></canvas>
        </div>
    @endif

    @forelse ($monitoringCharts as $index => $chart)
        <div class="feed-card mb-4">
            <h2 class="h6 mb-3">🩺 {{ $chart['label'] }} @if ($chart['unit'])<span class="text-muted small">({{ $chart['unit'] }})</span>@endif</h2>
            <canvas id="monitoringChart{{ $index }}" height="90"></canvas>
        </div>
    @empty
        @if (! $wellbeingChart)
            <div class="alert alert-info">
                Todavía no tienes suficientes registros para mostrar una gráfica — necesitas al menos 2 lecturas de monitoreo o 2 autorreportes de bienestar en fechas distintas. Sigue registrando y aquí vas a poder ver tu avance.
            </div>
        @endif
    @endforelse

    @if ($wellbeingChart || $monitoringCharts->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var palette = ['#7c3aed', '#ec4899', '#0ea5e9', '#22c55e', '#f97316'];

                @if ($wellbeingChart)
                    new Chart(document.getElementById('wellbeingChart'), {
                        type: 'line',
                        data: {
                            labels: @js($wellbeingChart['labels']),
                            datasets: [
                                @foreach ($wellbeingChart['series'] as $i => $series)
                                    {
                                        label: @js($series['label']),
                                        data: @js($series['values']),
                                        borderColor: palette[{{ $i }} % palette.length],
                                        backgroundColor: palette[{{ $i }} % palette.length],
                                        tension: 0.3,
                                    },
                                @endforeach
                            ],
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
                    });
                @endif

                @foreach ($monitoringCharts as $index => $chart)
                    new Chart(document.getElementById('monitoringChart{{ $index }}'), {
                        type: 'line',
                        data: {
                            labels: @js($chart['labels']),
                            datasets: [{
                                label: @js($chart['label']),
                                data: @js($chart['values']),
                                borderColor: palette[{{ $index }} % palette.length],
                                backgroundColor: palette[{{ $index }} % palette.length],
                                tension: 0.3,
                            }],
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } },
                    });
                @endforeach
            });
        </script>
    @endif
@endif
@endsection
