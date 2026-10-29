<x-filament-panels::page>
    @php
        $summary = $this->summary;
        $programStatus = match ($summary['program_status']) {
            'implementation' => 'En implementación',
            'active' => 'Activo',
            'suspended' => 'Suspendido',
            'archived' => 'Archivado',
            default => 'Sin configurar',
        };
        $totalIndicators = $summary['indicators_on_target'] + $summary['indicators_off_target'];
        $alerts = $summary['expired_evidence']
            + $summary['open_findings']
            + $summary['overdue_actions']
            + $summary['open_safety_events']
            + $summary['competencies_expired'];
        $primaryCards = [
            [
                'label' => 'Casos este mes',
                'value' => $summary['period_cases'],
                'detail' => now()->translatedFormat('F Y'),
                'color' => '#17375e',
                'background' => '#eff6ff',
            ],
            [
                'label' => 'Indicadores en meta',
                'value' => $summary['indicators_on_target'],
                'detail' => "de {$totalIndicators} evaluados",
                'color' => '#047857',
                'background' => '#ecfdf5',
            ],
            [
                'label' => 'Cumplimiento',
                'value' => number_format($summary['compliance_percentage'], 1, ',', '.').'%',
                'detail' => 'de estándares',
                'color' => '#6d28d9',
                'background' => '#f5f3ff',
            ],
            [
                'label' => 'Alertas abiertas',
                'value' => $alerts,
                'detail' => $alerts === 0 ? 'Sin pendientes críticos' : 'Requieren revisión',
                'color' => $alerts > 0 ? '#b45309' : '#047857',
                'background' => $alerts > 0 ? '#fffbeb' : '#ecfdf5',
            ],
        ];
        $secondaryCards = [
            ['label' => 'Evidencias vencidas', 'value' => $summary['expired_evidence'], 'alert' => $summary['expired_evidence'] > 0],
            ['label' => 'Evidencias por vencer (30 días)', 'value' => $summary['evidence_expiring_soon'], 'alert' => $summary['evidence_expiring_soon'] > 0],
            ['label' => 'Hallazgos abiertos', 'value' => $summary['open_findings'], 'alert' => $summary['open_findings'] > 0],
            ['label' => 'Acciones vencidas', 'value' => $summary['overdue_actions'], 'alert' => $summary['overdue_actions'] > 0],
            ['label' => 'Pendientes de evaluar', 'value' => $summary['pending_elements'], 'alert' => false],
            ['label' => 'Evidencias vigentes', 'value' => $summary['valid_evidence'], 'alert' => false],
            ['label' => 'Compromisos abiertos', 'value' => $summary['open_commitments'], 'alert' => false],
            ['label' => 'Eventos de seguridad abiertos', 'value' => $summary['open_safety_events'], 'alert' => $summary['open_safety_events'] > 0],
            ['label' => 'Competencias vencidas', 'value' => $summary['competencies_expired'], 'alert' => $summary['competencies_expired'] > 0],
            ['label' => 'Competencias por vencer (30 días)', 'value' => $summary['competencies_expiring_soon'], 'alert' => $summary['competencies_expiring_soon'] > 0],
            [
                'label' => 'Completitud del registro clínico',
                'value' => number_format($summary['data_completeness_percentage'], 1, ',', '.').'%',
                'alert' => $summary['data_completeness_percentage'] < 80,
            ],
        ];
    @endphp

    <div class="mx-auto w-full max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1">
                    <span class="h-2 w-2 rounded-full bg-blue-700"></span>
                    <span class="text-xs font-bold text-blue-900">{{ $programStatus }}</span>
                </div>
                <h2 class="text-xl font-bold text-slate-950">Centro de Excelencia de Sepsis</h2>
                <p class="mt-1 text-sm text-slate-600">Datos principales del periodo actual.</p>
            </div>

            <nav class="flex flex-wrap gap-2" aria-label="Accesos de Sepsis">
                <a
                    href="{{ url('/sepsis/indicadores') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-800 shadow-sm transition hover:border-blue-700 hover:text-blue-800"
                >
                    Indicadores
                </a>
                <a
                    href="{{ url('/sepsis/sepsis-cases') }}"
                    class="inline-flex items-center rounded-lg bg-[#17375e] px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#0d2340]"
                >
                    Casos de sepsis
                </a>
            </nav>
        </header>

        <section aria-labelledby="datos-principales">
            <h2 id="datos-principales" class="mb-3 text-sm font-bold text-slate-800">Datos principales</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($primaryCards as $card)
                    <article
                        class="min-h-32 rounded-xl border border-slate-200 p-4 shadow-sm"
                        style="background-color: {{ $card['background'] }}"
                    >
                        <p class="text-sm font-semibold text-slate-700">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-extrabold" style="color: {{ $card['color'] }}">
                            {{ $card['value'] }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-slate-600">{{ $card['detail'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="detalle-programa">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="detalle-programa" class="text-sm font-bold text-slate-800">Detalle del programa</h2>
                <span class="text-xs font-medium text-slate-500">Actualizado al {{ now()->format('d/m/Y') }}</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($secondaryCards as $card)
                    <article class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="pr-4 text-sm font-semibold text-slate-700">{{ $card['label'] }}</p>
                        <span @class([
                            'min-w-10 rounded-lg px-2.5 py-1 text-center text-lg font-extrabold',
                            'bg-amber-100 text-amber-800' => $card['alert'],
                            'bg-slate-100 text-slate-900' => ! $card['alert'],
                        ])>
                            {{ $card['value'] }}
                        </span>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="preparacion-institucional">
            @php
                $readiness = $summary['readiness'];
                $casePct = $readiness['threshold_cases'] > 0 ? min(100, round($readiness['cumulative_cases'] / $readiness['threshold_cases'] * 100)) : 0;
                $monthPct = $readiness['threshold_months'] > 0 ? min(100, round($readiness['streak_months'] / $readiness['threshold_months'] * 100)) : 0;
            @endphp
            <h2 id="preparacion-institucional" class="mb-3 text-sm font-bold text-slate-800">Preparación institucional (actividad sostenida)</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700">Pacientes gestionados bajo la guía (acumulado)</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950">
                        {{ $readiness['cumulative_cases'] }} <span class="text-sm font-medium text-slate-500">/ {{ $readiness['threshold_cases'] }} recomendados</span>
                    </p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-2 rounded-full {{ $readiness['meets_case_threshold'] ? 'bg-emerald-600' : 'bg-blue-600' }}" style="width: {{ $casePct }}%"></div>
                    </div>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700">Meses consecutivos con información reportada</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950">
                        {{ $readiness['streak_months'] }} <span class="text-sm font-medium text-slate-500">/ {{ $readiness['threshold_months'] }} recomendados</span>
                    </p>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-2 rounded-full {{ $readiness['meets_month_threshold'] ? 'bg-emerald-600' : 'bg-blue-600' }}" style="width: {{ $monthPct }}%"></div>
                    </div>
                </article>
            </div>
        </section>

        <section aria-labelledby="distribuciones">
            @php
                $distributions = $this->distributions;
                $palette = ['#17375e', '#5b7fb0', '#0f766e', '#b45309', '#b91c1c', '#7c3aed', '#94a3b8', '#0891b2'];
                $pies = [
                    ['title' => 'Servicio de origen', 'data' => $distributions['origin_service']],
                    ['title' => 'Foco infeccioso', 'data' => $distributions['infection_focus']],
                    ['title' => 'Sepsis comunitaria vs. hospitalaria', 'data' => $distributions['acquisition_type']],
                    ['title' => 'Población especial', 'data' => $distributions['special_population']],
                ];
            @endphp
            <h2 id="distribuciones" class="mb-3 text-sm font-bold text-slate-800">Distribución de casos del año</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($pies as $pie)
                    @php
                        $total = array_sum($pie['data']);
                        $stops = [];
                        $cursor = 0;
                        foreach (array_values($pie['data']) as $idx => $value) {
                            if ($value <= 0) { continue; }
                            $color = $palette[$idx % count($palette)];
                            $start = $total > 0 ? ($cursor / $total) * 100 : 0;
                            $cursor += $value;
                            $end = $total > 0 ? ($cursor / $total) * 100 : 0;
                            $stops[] = "{$color} {$start}% {$end}%";
                        }
                        $gradient = $stops ? implode(', ', $stops) : '#e2e8f0 0% 100%';
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-4 text-sm font-semibold text-slate-700">{{ $pie['title'] }}</h3>
                        @if ($total > 0)
                            <div class="flex items-center gap-5">
                                <div class="h-24 w-24 shrink-0 rounded-full" style="background: conic-gradient({{ $gradient }})"></div>
                                <div class="space-y-1 text-xs">
                                    @foreach (array_keys($pie['data']) as $idx => $label)
                                        @php $value = $pie['data'][$label]; @endphp
                                        @if ($value > 0)
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block h-3 w-3 rounded-sm" style="background: {{ $palette[$idx % count($palette)] }}"></span>
                                                <span class="text-slate-700">{{ $label }}</span>
                                                <span class="font-semibold text-slate-500">{{ $value }} ({{ round($value / $total * 100) }}%)</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-slate-400">Sin datos del año actual.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
