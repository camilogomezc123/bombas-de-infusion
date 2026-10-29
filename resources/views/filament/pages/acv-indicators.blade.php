<x-filament-panels::page>
    <div class="flex flex-col gap-6">
    @php
        $monthNames = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];
    @endphp

    {{-- Filtros principales --}}
    <div class="flex flex-wrap items-end justify-between gap-5 rounded-2xl border border-slate-200 p-5 shadow-sm" style="background-color: #ffffff; color: #0f172a;">
        <p class="max-w-xl text-sm font-semibold" style="color: #1e293b">
            Indicadores calculados desde los tiempos y desenlaces registrados. Los casos se cuentan en su
            <strong style="color: #0f172a">mes de egreso</strong>; los casos anulados y los aún hospitalizados no participan.
        </p>

        <div class="flex gap-3">
            <label class="w-32 text-sm font-semibold" style="color: #334155">
                Año
                <select wire:model.live="year" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" style="color: #0f172a; background-color: #ffffff; color-scheme: light;">
                    @foreach ($this->years as $availableYear)
                        <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                    @endforeach
                </select>
            </label>

            <label class="w-48 text-sm font-semibold" style="color: #334155">
                Mes
                <select wire:change="selectPeriod($event.target.value)" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" style="color: #0f172a; background-color: #ffffff; color-scheme: light;">
                    <option value="" @selected(! in_array($month, $this->months, true))>Seleccionar mes</option>
                    @foreach ($this->months as $availableMonth)
                        <option value="{{ $availableMonth }}" @selected($month === $availableMonth)>
                            {{ $monthNames[substr($availableMonth, 5)] }} {{ substr($availableMonth, 0, 4) }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    {{-- Selector rápido de período --}}
    <div class="relative z-0 rounded-2xl border border-slate-200 p-5 shadow-sm" style="clear: both; background-color: #ffffff; color: #0f172a;">
        <div class="mb-4">
            <div class="text-base font-extrabold text-slate-900">Períodos disponibles de {{ $year }}</div>
            <div class="mt-1 text-sm font-medium text-slate-600">Consulta el acumulado anual o un trimestre con datos registrados.</div>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($this->quickPeriods as $period => $periodLabel)
                <button
                    type="button"
                    wire:click="selectPeriod('{{ $period }}')"
                    title="{{ $periodLabel }}"
                    @class([
                        'rounded-lg border px-4 py-2 text-sm font-bold transition',
                        'border-[#17375e] bg-[#17375e] text-white shadow-sm' => $month === $period,
                        'border-slate-300 bg-white text-slate-800 hover:border-[#17375e] hover:text-[#17375e]' => $month !== $period,
                    ])
                >
                    {{ $period === \App\Filament\Acv\Pages\AcvIndicators::ALL_MONTHS ? 'Año completo' : strtoupper($period) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Vistas del tablero --}}
    <div class="flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-2 dark:bg-gray-800">
        @foreach (\App\Filament\Acv\Pages\AcvIndicators::VIEWS as $view => $viewLabel)
            <button
                type="button"
                wire:click="selectView('{{ $view }}')"
                @class([
                    'flex-1 rounded-xl px-5 py-3 text-sm font-bold transition sm:flex-none',
                    'bg-[#17375e] text-white shadow-md' => $dashboardView === $view,
                    'bg-transparent text-slate-800 hover:bg-white dark:text-gray-100 dark:hover:bg-gray-700' => $dashboardView !== $view,
                ])
            >
                {{ $viewLabel }}
            </button>
        @endforeach
    </div>

    @php $c = $this->current; @endphp

    @if ($c)
        @php
            $isAnnual = $month === \App\Filament\Acv\Pages\AcvIndicators::ALL_MONTHS;
            $isQuarter = array_key_exists($month, \App\Filament\Acv\Pages\AcvIndicators::QUARTERS);
            $periodLabel = match (true) {
                $isAnnual => 'del año',
                $isQuarter => 'del trimestre',
                default => 'del mes',
            };
            $countCards = [
                ['label' => 'Casos '.$periodLabel, 'value' => $c['total'], 'icon' => 'heroicon-m-clipboard-document-list'],
                ['label' => 'Trombólisis', 'value' => $c['thrombolysis'], 'icon' => 'heroicon-m-beaker'],
                ['label' => 'Trombectomías', 'value' => $c['thrombectomy'], 'icon' => 'heroicon-m-wrench-screwdriver'],
                ['label' => 'En ventana', 'value' => $c['in_window'], 'icon' => 'heroicon-m-clock'],
            ];

            $timeCards = [
                ['label' => 'Mediana puerta-imagen', 'value' => $this->formatMinutes($c['door_image'])],
                ['label' => 'Mediana puerta-aguja', 'value' => $this->formatMinutes($c['door_needle'])],
                ['label' => 'Mediana puerta-ingle', 'value' => $this->formatMinutes($c['door_groin'])],
                ['label' => 'Mediana ingle-recanalización', 'value' => $this->formatMinutes($c['groin_recanalization'])],
            ];

            $additionalIndicators = [
                ['label' => 'Hemorragia en tratados', 'value' => $c['treated_hemorrhage'], 'key' => 'hemorrhagic_transformation'],
            ];

            $distribution = [
                ['label' => 'Isquémico', 'value' => $c['ischemic'], 'color' => '#17375e'],
                ['label' => 'Hemorrágico', 'value' => $c['hemorrhagic'], 'color' => '#b91c1c'],
                ['label' => 'Imitador', 'value' => $c['mimics'], 'color' => '#d97706'],
                ['label' => 'TIA', 'value' => $c['tia'], 'color' => '#0f766e'],
                ['label' => 'Trombosis venosa', 'value' => $c['venous_thrombosis'], 'color' => '#7c3aed'],
            ];
            $classified = array_sum(array_column($distribution, 'value'));
            $otherCases = max(0, $c['total'] - $classified);
            if ($otherCases > 0) {
                $distribution[] = ['label' => 'Otros', 'value' => $otherCases, 'color' => '#94a3b8'];
            }

            $pieStops = [];
            $pieCursor = 0;
            foreach ($distribution as $item) {
                $start = $c['total'] > 0 ? ($pieCursor / $c['total']) * 100 : 0;
                $pieCursor += $item['value'];
                $end = $c['total'] > 0 ? ($pieCursor / $c['total']) * 100 : 0;
                $pieStops[] = "{$item['color']} {$start}% {$end}%";
            }
            $pieGradient = implode(', ', $pieStops);
        @endphp

        @if ($dashboardView === 'resq')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-gray-100">Clínica de Occidente</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-[#17375e] dark:text-white">ESO / WSO Angels Awards performance level</h2>
                </div>
                <div class="rounded-xl bg-[#17375e] px-5 py-3 text-right text-white shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-white/70">Período evaluado</div>
                    <div class="text-xl font-bold">{{ strtoupper(str_replace('/', ' ', $c['month'])) }}</div>
                </div>
            </div>

            <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-medium text-sky-950">
                Resultados calculados con la información registrada en el sistema. Son una estimación para revisión interna y no representan el estado final del premio emitido por RES-Q.
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-[1180px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#17375e] text-left text-xs font-bold uppercase tracking-wide text-white">
                        <tr>
                            <th class="px-4 py-3">Criterio ESO / WSO Angels Awards</th>
                            <th class="px-3 py-3 text-center">Estado</th>
                            <th class="px-3 py-3 text-center">Valor</th>
                            <th class="px-3 py-3 text-center">Casos totales</th>
                            <th class="px-3 py-3 text-center">Casos que cumplen</th>
                            <th class="px-3 py-3 text-center">Oro mínimo</th>
                            <th class="px-3 py-3 text-center">Platino mínimo</th>
                            <th class="px-3 py-3 text-center">Diamante mínimo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @foreach ($c['angels_metrics'] as $metric)
                            @php
                                $statusClasses = match ($metric['status']) {
                                    'Oro' => 'bg-amber-100 text-amber-900 ring-amber-300',
                                    'Platino' => 'bg-slate-200 text-slate-800 ring-slate-400',
                                    'Diamante' => 'bg-cyan-100 text-cyan-900 ring-cyan-300',
                                    default => 'bg-gray-100 text-gray-600 ring-gray-200',
                                };
                            @endphp
                            <tr class="align-top">
                                <td class="max-w-xl px-4 py-4 font-semibold text-slate-900">
                                    {{ $metric['label'] }}
                                    @if ($metric['note'])
                                        <div class="mt-1 text-xs font-medium text-slate-500">{{ $metric['note'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $statusClasses }}">
                                        {{ $metric['available'] ? ($metric['status'] ?? 'Sin nivel') : 'Sin datos capturados' }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center font-extrabold text-[#17375e]">{{ $this->formatPercentage($metric['value']) }}</td>
                                <td class="px-3 py-4 text-center">{{ $metric['total_cases'] ?? '—' }}</td>
                                <td class="px-3 py-4 text-center">{{ $metric['eligible_cases'] ?? '—' }}</td>
                                @foreach (['gold', 'platinum', 'diamond'] as $award)
                                    <td class="px-3 py-4 text-center font-semibold">
                                        {{ $metric[$award] === null ? '—' : number_format($metric[$award], 0, ',', '.').'%' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @else

        {{-- Distribución del período seleccionado --}}
        <section>
            <div
                onclick="window.acvChartModal.open(this, 'Distribución de casos del período')"
                class="cursor-zoom-in rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#5b7fb0]">Mezcla diagnóstica</p>
                <h2 class="mt-1 text-lg font-bold text-[#17375e] dark:text-white">Distribución de casos del período</h2>
                <div class="mt-6 flex flex-col items-center gap-8 sm:flex-row sm:justify-center">
                    <div class="relative h-52 w-52 shrink-0 rounded-full" style="background: conic-gradient({{ $pieGradient }});">
                        <div class="absolute inset-10 flex flex-col items-center justify-center rounded-full bg-white text-center shadow-inner dark:bg-gray-900">
                            <span class="text-3xl font-bold text-[#17375e] dark:text-white">{{ $c['total'] }}</span>
                            <span class="text-xs font-semibold uppercase text-gray-500">casos</span>
                        </div>
                    </div>
                    <div class="grid w-full max-w-sm grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($distribution as $item)
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800">
                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    <span class="h-3 w-3 rounded-full" style="background: {{ $item['color'] }}"></span>
                                    {{ $item['label'] }}
                                </div>
                                <div class="mt-1 text-xl font-bold text-[#17375e] dark:text-white">
                                    {{ $item['value'] }}
                                    <span class="text-xs font-medium text-gray-500">
                                        ({{ $c['total'] > 0 ? number_format(($item['value'] / $c['total']) * 100, 1, ',', '.') : 0 }}%)
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <div class="rounded-2xl bg-[#e8eef6] px-5 py-4 dark:bg-gray-800">
            <p class="text-sm font-bold text-slate-800 dark:text-gray-100">Análisis general</p>
            <h2 class="mt-1 text-2xl font-extrabold text-[#17375e] dark:text-white">Actividad, tiempos y desenlaces</h2>
        </div>

        {{-- Tarjetas de conteo (acento azul) --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($countCards as $card)
                <div class="rounded-2xl bg-gradient-to-br from-[#0d2340] to-[#17375e] p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-white/75">{{ $card['label'] }}</span>
                        <x-filament::icon :icon="$card['icon']" class="h-5 w-5 text-[#b8bec7]" />
                    </div>
                    <div class="mt-3 text-3xl font-bold">{{ number_format($card['value'], 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        {{-- Tarjetas de tiempos (medianas) --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($timeCards as $card)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</span>
                    <div class="mt-2 text-2xl font-bold text-[#17375e] dark:text-white">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Indicadores adicionales --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($additionalIndicators as $card)
                @php
                    $color = $this->targetColor($card['value'], $card['key']);
                    $dot = ['green' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'gray' => 'bg-gray-300'][$color];
                @endphp
                <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div>
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $card['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold text-[#17375e] dark:text-white">{{ $this->formatPercentage($card['value']) }}</div>
                    </div>
                    <span class="flex h-4 w-4 rounded-full {{ $dot }}"></span>
                </div>
            @endforeach
        </div>

        {{-- Barras apiladas de distribución diagnóstica por mes --}}
        @php
            $diagnosticSeries = [
                ['field' => 'ischemic', 'label' => 'Isquémico', 'short' => 'I', 'color' => '#17375e'],
                ['field' => 'hemorrhagic', 'label' => 'Hemorrágico', 'short' => 'H', 'color' => '#b91c1c'],
                ['field' => 'mimics', 'label' => 'Imitador', 'short' => 'IM', 'color' => '#d97706'],
                ['field' => 'tia', 'label' => 'TIA', 'short' => 'TIA', 'color' => '#0f766e'],
                ['field' => 'venous_thrombosis', 'label' => 'Trombosis venosa', 'short' => 'TV', 'color' => '#7c3aed'],
            ];
            $maxMonthlyCases = max(1, max(array_column($this->rows, 'total') ?: [0]));
        @endphp

        <section
            onclick="window.acvChartModal.open(this, 'Distribución mensual de casos por diagnóstico')"
            class="cursor-zoom-in rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#5b7fb0]">Evolución mensual</p>
                    <h2 class="text-lg font-bold text-[#17375e] dark:text-white">Distribución de casos por diagnóstico</h2>
                </div>
                <div class="flex flex-wrap gap-3 text-xs font-semibold text-gray-600 dark:text-gray-300">
                    @foreach ($diagnosticSeries as $series)
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-sm" style="background: {{ $series['color'] }}"></span>
                            {{ $series['label'] }}
                        </span>
                    @endforeach
                    <span class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm bg-slate-400"></span>
                        Otros / indeterminado
                    </span>
                </div>
            </div>

            <div class="mt-6 flex min-w-[820px] items-end gap-3 overflow-x-auto pb-2" style="height: 340px">
                @foreach ($this->rows as $row)
                    @php
                        $principalTotal = array_sum(array_map(fn ($series) => $row[$series['field']], $diagnosticSeries));
                        $otherTotal = max(0, $row['total'] - $principalTotal);
                        $barHeight = max(4, ($row['total'] / $maxMonthlyCases) * 190);
                    @endphp
                    <div class="flex min-w-16 flex-1 flex-col items-center justify-end">
                        <div
                            class="flex w-full max-w-20 flex-col-reverse overflow-hidden rounded-t-md bg-slate-100"
                            style="height: {{ $barHeight }}px"
                        >
                            @foreach ($diagnosticSeries as $series)
                                @php
                                    $segmentHeight = $row['total'] > 0 ? ($row[$series['field']] / $row['total']) * 100 : 0;
                                @endphp
                                @if ($row[$series['field']] > 0)
                                    <div
                                        class="flex items-center justify-center px-1 text-[10px] font-extrabold text-white"
                                        style="height: {{ $segmentHeight }}%; background: {{ $series['color'] }}"
                                        title="{{ $row['month'] }} · {{ $series['label'] }}: {{ $row[$series['field']] }}"
                                    >
                                        {{ $row[$series['field']] }}
                                    </div>
                                @endif
                            @endforeach
                            @if ($otherTotal > 0)
                                <div
                                    class="flex items-center justify-center bg-slate-500 px-1 text-[10px] font-extrabold text-white"
                                    style="height: {{ ($otherTotal / $row['total']) * 100 }}%"
                                    title="{{ $row['month'] }} · Otros o indeterminado: {{ $otherTotal }}"
                                >
                                    {{ $otherTotal }}
                                </div>
                            @endif
                        </div>
                        <span class="mt-2 text-xs font-extrabold" style="color: #dbeafe">{{ $monthNames[substr($row['month'], 5)] }}</span>
                        <span class="text-[11px] font-bold" style="color: #bfdbfe">({{ $row['total'] }} casos)</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Gráficas de tendencia por mes (barras en CSS, sin dependencias) --}}
        @php
            $maxCasos = max(array_map(fn ($r) => $r['total'], $this->rows) ?: [1]);
            $charts = [
                ['title' => 'Casos por mes', 'fmt' => 'int', 'max' => $maxCasos ?: 1, 'field' => 'total', 'class' => 'bg-[#17375e]'],
                ['title' => 'Cumplimiento puerta-aguja ≤ 60 min', 'fmt' => 'pct', 'max' => 100, 'field' => 'needle_60', 'class' => 'bg-emerald-500'],
                ['title' => 'Cumplimiento puerta-ingle ≤ 120 min', 'fmt' => 'pct', 'max' => 100, 'field' => 'groin_120', 'class' => 'bg-[#5b7fb0]'],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ($charts as $chart)
                <div
                    onclick="window.acvChartModal.open(this, {{ \Illuminate\Support\Js::from($chart['title']) }})"
                    class="cursor-zoom-in rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $chart['title'] }}</h3>
                    <div class="flex h-40 items-end gap-2">
                        @foreach ($this->rows as $row)
                            @php
                                $raw = $row[$chart['field']];
                                $val = $raw ?? 0;
                                $pct = $chart['max'] > 0 ? min(100, round(($val / $chart['max']) * 100)) : 0;
                                $barPx = max(4, (int) round($pct * 1.15));
                                $label = $chart['fmt'] === 'pct' ? ($raw === null ? '—' : round($raw).'%') : $val;
                            @endphp
                            <div class="flex flex-1 flex-col items-center justify-end gap-1" title="{{ $row['month'] }}: {{ $label }}">
                                <span class="text-[10px] font-bold" style="color: #dbeafe">{{ $label }}</span>
                                <div
                                    class="w-full rounded-t {{ $chart['class'] }}"
                                    style="height: {{ $barPx }}px"
                                ></div>
                                <span class="text-[10px] font-bold" style="color: #bfdbfe">{{ substr($row['month'], 5) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Gráficas combinadas: barras (cantidad) + línea (tiempo mediana) por mes --}}
        @php
            $comboCharts = [
                ['title' => 'Trombólisis por mes', 'count' => 'thrombolysis', 'time' => 'door_needle', 'bar' => '#17375e', 'timeLabel' => 'Mediana puerta-aguja (min)'],
                ['title' => 'Trombectomías por mes', 'count' => 'thrombectomy', 'time' => 'door_groin', 'bar' => '#5b7fb0', 'timeLabel' => 'Mediana puerta-ingle (min)'],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ($comboCharts as $combo)
                @php
                    $rows = $this->rows;
                    $n = max(1, count($rows));
                    $counts = array_map(fn ($r) => (int) $r[$combo['count']], $rows);
                    $maxCount = max(1, max($counts ?: [0]));
                    $timeVals = array_filter(array_map(fn ($r) => $r[$combo['time']], $rows), fn ($t) => $t !== null);
                    $maxTime = max(1, $timeVals ? max($timeVals) : 1);
                    $W = 290; $H = 160; $padTop = 20; $padBottom = 22; $padX = 5;
                    $plotH = $H - $padTop - $padBottom;
                    $plotW = $W - $padX * 2;
                    $slot = $plotW / $n;
                    $barW = $slot * 0.92;
                    $points = [];
                    foreach ($rows as $i => $r) {
                        $t = $r[$combo['time']];
                        if ($t !== null) {
                            $cx = $padX + $slot * $i + $slot / 2;
                            $py = $padTop + $plotH - ($t / $maxTime) * $plotH;
                            $points[] = ['x' => $cx, 'y' => $py, 't' => $t];
                        }
                    }
                    $poly = implode(' ', array_map(fn ($p) => round($p['x'], 1).','.round($p['y'], 1), $points));
                @endphp
                <div
                    onclick="window.acvChartModal.open(this, {{ \Illuminate\Support\Js::from($combo['title']) }})"
                    class="cursor-zoom-in rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $combo['title'] }}</h3>
                    <div class="mb-3 mt-1 flex flex-wrap items-center gap-4 text-[11px] text-gray-600">
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-sm" style="background: {{ $combo['bar'] }}"></span>Cantidad</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-[2px] w-4" style="background: #d97706"></span>{{ $combo['timeLabel'] }}</span>
                    </div>
                    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="w-full" preserveAspectRatio="xMidYMid meet">
                        @foreach ($rows as $i => $r)
                            @php
                                $cx = $padX + $slot * $i + $slot / 2;
                                $cnt = (int) $r[$combo['count']];
                                $barH = ($cnt / $maxCount) * $plotH;
                                $barY = $padTop + $plotH - $barH;
                            @endphp
                            <rect x="{{ round($cx - $barW / 2, 1) }}" y="{{ round($barY, 1) }}" width="{{ round($barW, 1) }}" height="{{ round(max(0, $barH), 1) }}" rx="2" fill="{{ $combo['bar'] }}" />
                            @if ($cnt > 0)
                            <text x="{{ round($cx, 1) }}" y="{{ round($barY - 4, 1) }}" text-anchor="middle" font-size="9" font-weight="700" fill="#dbeafe">{{ $cnt }}</text>
                            @endif
                            <text x="{{ round($cx, 1) }}" y="{{ $H - 9 }}" text-anchor="middle" font-size="9" font-weight="700" fill="#bfdbfe">{{ substr($r['month'], 5) }}</text>
                        @endforeach

                        @if (count($points) > 1)
                            <polyline points="{{ $poly }}" fill="none" stroke="#d97706" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                        @endif
                        @foreach ($points as $p)
                            <circle cx="{{ round($p['x'], 1) }}" cy="{{ round($p['y'], 1) }}" r="2.6" fill="#d97706" />
                            <text x="{{ round($p['x'], 1) }}" y="{{ round($p['y'] - 5, 1) }}" text-anchor="middle" font-size="8" font-weight="700" fill="#dbeafe">{{ $p['t'] }}</text>
                        @endforeach
                    </svg>
                </div>
            @endforeach
        </div>
        @endif
    @else
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500 dark:bg-gray-900">
            No hay datos para el período seleccionado.
        </div>
    @endif

    {{-- Tabla detallada (réplica del tablero institucional) --}}
    @if ($dashboardView === 'general')
    <details class="group rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900">
        <summary class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-[#17375e] dark:text-white">
            <span class="inline-flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-table-cells" class="h-5 w-5" />
                Ver tabla mensual completa
            </span>
        </summary>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="min-w-[2200px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-[#17375e] text-left text-xs font-semibold uppercase tracking-wide text-white">
                    <tr>
                        <th class="sticky left-0 z-10 bg-[#17375e] px-4 py-3">Mes</th>
                        <th class="px-3 py-3">Casos</th>
                        <th class="px-3 py-3">Fuera ventana</th>
                        <th class="px-3 py-3">En ventana</th>
                        <th class="px-3 py-3">Imitadores</th>
                        <th class="px-3 py-3">P-Imagen</th>
                        <th class="px-3 py-3">Trombólisis</th>
                        <th class="px-3 py-3">P-Aguja</th>
                        <th class="px-3 py-3">≤60 min</th>
                        <th class="px-3 py-3">≤45 min</th>
                        <th class="px-3 py-3">Trombectomía</th>
                        <th class="px-3 py-3">P-Ingle</th>
                        <th class="px-3 py-3">≤120 min</th>
                        <th class="px-3 py-3">≤90 min</th>
                        <th class="px-3 py-3">P-Fono</th>
                        <th class="px-3 py-3">Cumple Fono</th>
                        <th class="px-3 py-3">Isquémico</th>
                        <th class="px-3 py-3">TIA</th>
                        <th class="px-3 py-3">Hemorrágico</th>
                        <th class="px-3 py-3">HSA</th>
                        <th class="px-3 py-3">HIC</th>
                        <th class="px-3 py-3">Trombosis venosa</th>
                        <th class="px-3 py-3">Trombólisis/isquémicos</th>
                        <th class="px-3 py-3">Elegibles</th>
                        <th class="px-3 py-3">Trombolizados elegibles</th>
                        <th class="px-3 py-3">Tratados</th>
                        <th class="px-3 py-3">Tratados ventana</th>
                        <th class="px-3 py-3">Ingle-recanalización</th>
                        <th class="px-3 py-3">Mortalidad isquémica</th>
                        <th class="px-3 py-3">Mortalidad hemorrágica</th>
                        <th class="px-3 py-3">Transformación hemorrágica</th>
                        <th class="px-3 py-3">Hemorragia tratados</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 dark:divide-gray-800 dark:text-gray-200">
                    @forelse ($this->rows as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-800">
                            <td class="sticky left-0 bg-white px-4 py-3 font-semibold text-[#17375e] dark:bg-gray-900 dark:text-white">{{ $row['month'] }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $row['total'] }}</td>
                            <td class="px-3 py-3">{{ $row['outside_window'] }}</td>
                            <td class="px-3 py-3">{{ $row['in_window'] }}</td>
                            <td class="px-3 py-3">{{ $row['mimics'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatMinutes($row['door_image']) }}</td>
                            <td class="px-3 py-3">{{ $row['thrombolysis'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatMinutes($row['door_needle']) }}</td>
                            <td class="px-3 py-3"><span class="rounded-full px-2 py-1 font-semibold {{ $this->complianceColor($row['needle_60'], 85) }}">{{ $this->formatPercentage($row['needle_60']) }}</span></td>
                            <td class="px-3 py-3"><span class="rounded-full px-2 py-1 font-semibold {{ $this->complianceColor($row['needle_45'], 75) }}">{{ $this->formatPercentage($row['needle_45']) }}</span></td>
                            <td class="px-3 py-3">{{ $row['thrombectomy'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatMinutes($row['door_groin']) }}</td>
                            <td class="px-3 py-3"><span class="rounded-full px-2 py-1 font-semibold {{ $this->complianceColor($row['groin_120'], 80) }}">{{ $this->formatPercentage($row['groin_120']) }}</span></td>
                            <td class="px-3 py-3"><span class="rounded-full px-2 py-1 font-semibold {{ $this->complianceColor($row['groin_90'], 50) }}">{{ $this->formatPercentage($row['groin_90']) }}</span></td>
                            <td class="px-3 py-3">{{ $this->formatMinutes($row['door_speech']) }}</td>
                            <td class="px-3 py-3">{{ $this->formatPercentage($row['speech_compliance']) }}</td>
                            <td class="px-3 py-3">{{ $row['ischemic'] }}</td>
                            <td class="px-3 py-3">{{ $row['tia'] }}</td>
                            <td class="px-3 py-3">{{ $row['hemorrhagic'] }}</td>
                            <td class="px-3 py-3">{{ $row['hsa'] }}</td>
                            <td class="px-3 py-3">{{ $row['hic'] }}</td>
                            <td class="px-3 py-3">{{ $row['venous_thrombosis'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatPercentage($row['thrombolysis_ischemic']) }}</td>
                            <td class="px-3 py-3">{{ $row['eligible_ischemic'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatPercentage($row['eligible_thrombolysed']) }}</td>
                            <td class="px-3 py-3">{{ $row['treated'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatPercentage($row['treated_window']) }}</td>
                            <td class="px-3 py-3">{{ $this->formatMinutes($row['groin_recanalization']) }}</td>
                            <td class="px-3 py-3">{{ $row['ischemic_deaths'] }} ({{ $this->formatPercentage($row['ischemic_mortality']) }})</td>
                            <td class="px-3 py-3">{{ $row['hemorrhagic_deaths'] }} ({{ $this->formatPercentage($row['hemorrhagic_mortality']) }})</td>
                            <td class="px-3 py-3">{{ $row['hemorrhagic_transformation'] }}</td>
                            <td class="px-3 py-3">{{ $this->formatPercentage($row['treated_hemorrhage']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="32" class="px-6 py-12 text-center text-gray-500">No hay datos para el año seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
    @endif

        <div
            id="acv-chart-modal"
            class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/55 p-6 sm:p-12"
            onclick="window.acvChartModal.close()"
        >
            <div
                onclick="event.stopPropagation()"
                class="flex max-h-[82vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#5b7fb0]">Vista ampliada</p>
                        <h2 id="acv-chart-modal-title" class="text-lg font-extrabold text-[#17375e]"></h2>
                    </div>
                    <button
                        type="button"
                        onclick="window.acvChartModal.close()"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100"
                    >
                        Cerrar
                    </button>
                </div>

                <div id="acv-chart-modal-canvas" class="min-h-64 flex-1 overflow-auto p-4 sm:p-6">
                    <div id="acv-chart-modal-loading" class="flex min-h-64 items-center justify-center text-sm font-semibold text-slate-600">
                        Generando imagen...
                    </div>
                    <img
                        id="acv-chart-modal-image"
                        alt=""
                        class="mx-auto hidden max-h-[58vh] max-w-full rounded-xl bg-white object-contain shadow-sm"
                    >
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4">
                    <p id="acv-chart-modal-status" class="text-sm font-semibold text-slate-600"></p>
                    <button
                        id="acv-chart-modal-copy"
                        type="button"
                        onclick="window.acvChartModal.copy()"
                        disabled
                        class="rounded-lg bg-[#17375e] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#244f7f] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Copiar como imagen
                    </button>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/acv-charts.js')

    @script
    <script>
        window.acvChartModal = {
            imageUrl: null,
            request: 0,
            elements() {
                return {
                    modal: document.getElementById('acv-chart-modal'),
                    title: document.getElementById('acv-chart-modal-title'),
                    loading: document.getElementById('acv-chart-modal-loading'),
                    image: document.getElementById('acv-chart-modal-image'),
                    canvas: document.getElementById('acv-chart-modal-canvas'),
                    status: document.getElementById('acv-chart-modal-status'),
                    copy: document.getElementById('acv-chart-modal-copy'),
                };
            },
            async open(element, title) {
                const request = ++this.request;
                const ui = this.elements();

                if (! ui.modal) return;

                this.releaseImage();
                ui.title.textContent = title;
                const elementBackground = window.getComputedStyle(element).backgroundColor;
                ui.canvas.style.backgroundColor = elementBackground === 'rgba(0, 0, 0, 0)'
                    ? window.getComputedStyle(document.body).backgroundColor
                    : elementBackground;
                ui.loading.textContent = 'Generando imagen...';
                ui.loading.classList.remove('hidden');
                ui.loading.classList.add('flex');
                ui.image.classList.add('hidden');
                ui.image.removeAttribute('src');
                ui.status.textContent = '';
                ui.copy.disabled = true;
                ui.modal.classList.remove('hidden');
                ui.modal.classList.add('flex');

                try {
                    const blob = await window.acvChartImage.capture(element);

                    if (request !== this.request) return;

                    this.imageUrl = URL.createObjectURL(blob);
                    ui.image.src = this.imageUrl;
                    ui.image.alt = title;
                    ui.image.classList.remove('hidden');
                    ui.loading.classList.add('hidden');
                    ui.loading.classList.remove('flex');
                    ui.copy.disabled = false;
                } catch (error) {
                    console.error(error);
                    ui.loading.textContent = 'No fue posible generar la imagen de esta gráfica.';
                }
            },
            close() {
                const ui = this.elements();

                this.request++;
                this.releaseImage();

                if (! ui.modal) return;

                ui.modal.classList.add('hidden');
                ui.modal.classList.remove('flex');
                ui.image.classList.add('hidden');
                ui.status.textContent = '';
            },
            async copy() {
                const ui = this.elements();

                if (! this.imageUrl) return;

                ui.status.textContent = 'Copiando...';
                const blob = await fetch(this.imageUrl).then(response => response.blob());

                try {
                    await navigator.clipboard.write([
                        new ClipboardItem({ 'image/png': blob }),
                    ]);
                    ui.status.textContent = 'Imagen copiada. Ya puedes pegarla en una presentación o mensaje.';
                } catch (error) {
                    const link = document.createElement('a');
                    link.href = this.imageUrl;
                    link.download = `${ui.title.textContent || 'grafica'}.png`;
                    link.click();
                    ui.status.textContent = 'El navegador no permitió copiarla; la imagen se descargó en PNG.';
                }
            },
            releaseImage() {
                if (this.imageUrl) {
                    URL.revokeObjectURL(this.imageUrl);
                    this.imageUrl = null;
                }
            },
        };

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                window.acvChartModal.close();
            }
        });

    </script>
    @endscript
</x-filament-panels::page>
