@extends('portal.layout')

@section('title', 'Resumen para tu cita')

@section('content')

@if (! $case)
    <div class="alert alert-warning">Todavía no tienes un programa de seguimiento activo.</div>
@else
    <div class="no-print d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">🖨️ Resumen para tu cita</h1>
            <p class="text-muted mb-0">Una página lista para imprimir o mostrar en tu celular en tu próxima consulta.</p>
        </div>
        <button type="button" class="btn btn-game" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    </div>

    <div id="summaryPrintArea">
        <div class="mb-4">
            <h2 class="h4 mb-1">Resumen de recuperación — {{ $case->patient->full_name }}</h2>
            <p class="text-muted small mb-0">
                Caso {{ $case->case_number }} · Identificación {{ $case->patient->identification }} · Etapa: {{ $case->clinicalStageLabel() }}
                <br>Generado el {{ $printedAt->format('d/m/Y H:i') }}
            </p>
        </div>

        @if ($upcomingAgenda->isNotEmpty())
            <div class="feed-card mb-3">
                <h3 class="h6 mb-2">📅 Próximas citas y terapias</h3>
                <ul class="mb-0">
                    @foreach ($upcomingAgenda as $item)
                        <li>{{ $item->scheduled_at->format('d/m/Y H:i') }} — {{ \App\Models\PicsAgendaItem::TYPES[$item->type] ?? $item->type }}: {{ $item->title }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="feed-card mb-3">
            <h3 class="h6 mb-2">💊 Medicamentos conciliados</h3>
            @if ($medications->isEmpty())
                <p class="text-muted small mb-0">Sin medicamentos conciliados registrados.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Medicamento</th><th>Dosis</th><th>Vía</th><th>Frecuencia</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($medications as $item)
                                <tr>
                                    <td>{{ $item->medication_name }}</td>
                                    <td>{{ $item->dose }}</td>
                                    <td>{{ \App\Models\MedicationReconciliationItem::ROUTES[$item->route] ?? $item->route }}</td>
                                    <td>{{ $item->frequency }}</td>
                                    <td>{{ $item->statusLabel() }}</td>
                                </tr>
                                @if ($item->patient_instructions)
                                    <tr><td colspan="5" class="text-muted small">↳ {{ $item->patient_instructions }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="feed-card mb-3">
            <h3 class="h6 mb-2">🎯 Metas de recuperación activas</h3>
            @if ($activeGoals->isEmpty())
                <p class="text-muted small mb-0">Sin metas activas registradas.</p>
            @else
                <ul class="mb-0">
                    @foreach ($activeGoals as $entry)
                        <li class="mb-1">
                            <strong>{{ $entry['goal']->description }}</strong>
                            @if ($entry['goal']->target_date)
                                <span class="text-muted small">(meta al {{ $entry['goal']->target_date->format('d/m/Y') }})</span>
                            @endif
                            @if ($entry['latestReport'])
                                <br><span class="text-muted small">Último avance reportado el {{ $entry['latestReport']->reported_at->format('d/m/Y') }}{{ $entry['latestReport']->had_difficulty ? ' — con dificultad' : '' }}{{ $entry['latestReport']->notes ? ': "'.$entry['latestReport']->notes.'"' : '' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="feed-card mb-3">
            <h3 class="h6 mb-2">🩺 Últimas lecturas de monitoreo en casa</h3>
            @if ($recentReadings->isEmpty())
                <p class="text-muted small mb-0">Sin lecturas registradas todavía.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Fecha</th><th>Medición</th><th>Valor</th></tr></thead>
                        <tbody>
                            @foreach ($recentReadings as $reading)
                                <tr>
                                    <td>{{ $reading->measured_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $reading->typeLabel() }}</td>
                                    <td>{{ $reading->value }} {{ $reading->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="feed-card mb-3">
            <h3 class="h6 mb-2">💙 Último autorreporte de bienestar</h3>
            @if (! $latestWellbeing)
                <p class="text-muted small mb-0">Sin autorreportes registrados todavía.</p>
            @else
                @php($semaforoEmoji = fn (string $s) => match ($s) { 'verde' => '🟢', 'amarillo' => '🟡', 'rojo' => '🔴', default => '⚪' })
                <p class="small mb-2">{{ $latestWellbeing->checkpointLabel() }} · reportado el {{ $latestWellbeing->followed_up_at->format('d/m/Y') }} por {{ $latestWellbeing->respondent_type === 'familia' ? 'el cuidador/familia' : 'el paciente' }}</p>
                @if ($latestWellbeing->respondent_type === 'familia')
                    <p class="mb-0">{{ $semaforoEmoji($latestWellbeing->semaforoPicsf()) }} Carga del cuidador</p>
                @else
                    <ul class="list-unstyled mb-0">
                        <li>{{ $semaforoEmoji($latestWellbeing->semaforoAnsiedad()) }} Ansiedad (HADS-A)</li>
                        <li>{{ $semaforoEmoji($latestWellbeing->semaforoDepresion()) }} Ánimo (PHQ-9)</li>
                        <li>{{ $semaforoEmoji($latestWellbeing->semaforoPtsd()) }} Estrés postraumático</li>
                        <li>{{ $semaforoEmoji($latestWellbeing->semaforoFatiga()) }} Fatiga</li>
                        <li>{{ $semaforoEmoji($latestWellbeing->semaforoDolor()) }} Dolor</li>
                    </ul>
                @endif
                <p class="text-muted small mt-2 mb-0">🟢 Sin alertas · 🟡 Atención moderada · 🔴 Atención prioritaria · ⚪ Sin dato</p>
            @endif
        </div>

        @if ($openSupportRequests->isNotEmpty())
            <div class="feed-card mb-3">
                <h3 class="h6 mb-2">🆘 Dudas o dificultades sin responder aún</h3>
                <ul class="mb-0">
                    @foreach ($openSupportRequests as $request)
                        <li>[{{ \App\Models\SupportRequest::TYPES[$request->type] ?? $request->type }}] {{ $request->description }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-muted small">Generado automáticamente desde POSUCI 360 Conecta. Este resumen es una ayuda para la conversación con tu equipo médico — no reemplaza la valoración clínica.</p>
    </div>
@endif
@endsection
