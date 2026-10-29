<x-filament-panels::page>
    {{-- Filtros --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <p class="max-w-xl text-sm font-medium" style="color: #334155">
            Indicadores del Código Sepsis calculados sobre los casos válidos y cerrados. Los casos sin egreso
            (aún en proceso) no participan en los indicadores mensuales.
        </p>

        <div class="flex gap-3">
            <label class="w-32 text-sm font-semibold" style="color: #334155">
                Año
                <select wire:model.live="year" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" style="color:#0f172a;background:#fff;color-scheme:light;">
                    @foreach ($this->years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <label class="w-40 text-sm font-semibold" style="color: #334155">
                Período
                <select wire:model.live="month" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm" style="color:#0f172a;background:#fff;color-scheme:light;">
                    <option value="all">Todo el año</option>
                    @foreach ($this->months as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    @php $c = $this->current; @endphp

    @if ($c)
        {{-- Los 6 indicadores principales --}}
        @php
            $indicators = [
                ['key' => 'bundle_pct', 'n' => 1, 'title' => 'Adherencia al bundle de la primera hora', 'detail' => 'Antibiótico + interpretación del lactato + hemocultivos, todos ≤ 60 min tras la activación.', 'den' => $c['activated_total'], 'den_label' => 'casos con código activado'],
                ['key' => 'map_goal_pct', 'n' => 2, 'title' => 'Meta de PAM en las primeras 3 horas', 'detail' => 'PAM ≥ 65 mmHg dentro de las 3 h posteriores a la activación, en choque séptico.', 'den' => $c['shock_total'], 'den_label' => 'casos con choque séptico'],
                ['key' => 'mort_hosp_sepsis_pct', 'n' => 3, 'title' => 'Mortalidad hospitalaria por sepsis', 'detail' => 'Fallecidos durante la hospitalización entre los casos de sepsis sin choque.', 'den' => $c['sepsis_total'], 'den_label' => 'casos de sepsis sin choque'],
                ['key' => 'mort_hosp_shock_pct', 'n' => 4, 'title' => 'Mortalidad hospitalaria por choque séptico', 'detail' => 'Fallecidos durante la hospitalización entre los casos con choque séptico.', 'den' => $c['shock_total'], 'den_label' => 'casos con choque séptico'],
                ['key' => 'mort_30d_sepsis_pct', 'n' => 5, 'title' => 'Mortalidad a 30 días por sepsis', 'detail' => 'Fallecidos dentro de los 30 días posteriores a la activación, sepsis sin choque.', 'den' => $c['sepsis_total'], 'den_label' => 'casos de sepsis sin choque'],
                ['key' => 'mort_30d_shock_pct', 'n' => 6, 'title' => 'Mortalidad a 30 días por choque séptico', 'detail' => 'Fallecidos dentro de los 30 días posteriores a la activación, con choque séptico.', 'den' => $c['shock_total'], 'den_label' => 'casos con choque séptico'],
            ];
        @endphp

        <div>
            <h2 class="mb-3 text-base font-bold text-[#17375e] dark:text-white">Indicadores principales</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($indicators as $ind)
                    @php
                        $value = $c[$ind['key']];
                        $meets = $this->meetsGoal($ind['key'], $value);
                    @endphp
                    <div class="rounded-2xl border bg-white p-5 shadow-sm dark:bg-gray-900 {{ $meets === null ? 'border-gray-200 dark:border-gray-700' : ($meets ? 'border-emerald-300 dark:border-emerald-800' : 'border-red-300 dark:border-red-800') }}">
                        <div class="flex items-start justify-between gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#17375e] text-sm font-bold text-white">{{ $ind['n'] }}</span>
                            @if ($meets === null)
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">Sin dato</span>
                            @elseif ($meets)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Cumple</span>
                            @else
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/50 dark:text-red-300">No cumple</span>
                            @endif
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $ind['title'] }}</h3>
                        <div class="mt-2 flex items-baseline gap-3">
                            <span class="text-3xl font-bold {{ $meets === null ? 'text-gray-400' : ($meets ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $this->formatPercentage($value) }}
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $this->goalText($ind['key']) }}</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $ind['detail'] }}</p>
                        <p class="mt-1 text-xs font-medium text-gray-400">n = {{ $ind['den'] }} {{ $ind['den_label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Desglose del bundle (indicador 1) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Desglose del bundle de la primera hora</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Cumplimiento de cada componente dentro de los 60 minutos posteriores a la activación del código ({{ $c['activated_total'] }} casos con hora de activación).</p>
            @php
                $components = [
                    ['label' => 'Administración del antibiótico ≤ 60 min', 'value' => $c['bundle_ab_pct'], 'color' => '#5b7fb0'],
                    ['label' => 'Interpretación del lactato ≤ 60 min', 'value' => $c['bundle_lactate_pct'], 'color' => '#0f766e'],
                    ['label' => 'Toma de hemocultivos ≤ 60 min', 'value' => $c['bundle_culture_pct'], 'color' => '#b45309'],
                    ['label' => 'Bundle completo (los 3 componentes)', 'value' => $c['bundle_pct'], 'color' => '#17375e'],
                ];
            @endphp
            <div class="space-y-3">
                @foreach ($components as $comp)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $comp['label'] }}</span>
                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $this->formatPercentage($comp['value']) }}</span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-2.5 rounded-full" style="width: {{ min(100, $comp['value'] ?? 0) }}%; background: {{ $comp['color'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tarjetas de contexto --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Casos', 'value' => number_format($c['total'], 0, ',', '.'), 'icon' => 'heroicon-m-clipboard-document-list'],
                    ['label' => 'Con choque séptico', 'value' => number_format($c['shock_total'], 0, ',', '.'), 'icon' => 'heroicon-m-exclamation-triangle'],
                    ['label' => 'Mediana diagnóstico → antibiótico', 'value' => $this->formatMinutes($c['diag_ab']), 'icon' => 'heroicon-m-beaker'],
                    ['label' => 'Mediana diagnóstico → drenaje', 'value' => $this->formatMinutes($c['diag_drainage']), 'icon' => 'heroicon-m-wrench-screwdriver'],
                ];
            @endphp
            @foreach ($cards as $card)
                <div class="rounded-2xl bg-gradient-to-br from-[#0d2340] to-[#17375e] p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-white/75">{{ $card['label'] }}</span>
                        <x-filament::icon :icon="$card['icon']" class="h-5 w-5 text-[#b8bec7]" />
                    </div>
                    <div class="mt-3 text-3xl font-bold">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Estancias y cumplimientos complementarios --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @php
                $sec = [
                    ['label' => 'Mediana estancia en urgencias', 'value' => $this->formatHours($c['er_stay_hours'])],
                    ['label' => 'Mediana estancia en CDO', 'value' => $this->formatDays($c['clinic_stay_days'])],
                    ['label' => 'Mediana estancia en UCI', 'value' => $this->formatDays($c['uci_stay_days'])],
                    ['label' => 'Cumplimiento cultivo previo al AB', 'value' => $this->formatPercentage($c['culture_before_pct'])],
                    ['label' => 'Ajuste de AB según cultivo', 'value' => $this->formatPercentage($c['ab_adjusted_pct'])],
                ];
            @endphp
            @foreach ($sec as $card)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</span>
                    <div class="mt-2 text-2xl font-bold text-[#17375e] dark:text-white">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Evolución mensual de los 6 indicadores --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-4 text-sm font-bold text-[#17375e] dark:text-white">Evolución mensual de los indicadores</h3>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                            <th class="py-2 pr-3 font-semibold">Mes</th>
                            <th class="py-2 pr-3 font-semibold">Casos</th>
                            @foreach ($indicators as $ind)
                                <th class="py-2 pr-3 font-semibold">{{ $ind['n'] }}. {{ $ind['title'] }}<br><span class="font-normal text-gray-400">{{ $this->goalText($ind['key']) }}</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->rows as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-3 font-semibold text-gray-700 dark:text-gray-200">{{ $row['month'] }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $row['total'] }}</td>
                                @foreach ($indicators as $ind)
                                    @php $meets = $this->meetsGoal($ind['key'], $row[$ind['key']]); @endphp
                                    <td class="py-2 pr-3 font-bold {{ $meets === null ? 'text-gray-400' : ($meets ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400') }}">
                                        {{ $this->formatPercentage($row[$ind['key']]) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Gráficas de barras por mes --}}
        @php
            $barCharts = [
                ['title' => 'Casos por mes', 'field' => 'total', 'type' => 'int', 'class' => 'bg-[#17375e]'],
                ['title' => 'Adherencia al bundle (≤ 60 min)', 'field' => 'bundle_pct', 'type' => 'pct', 'class' => 'bg-emerald-600'],
                ['title' => 'Meta de PAM en 3 h (choque)', 'field' => 'map_goal_pct', 'type' => 'pct', 'class' => 'bg-[#0891b2]'],
                ['title' => 'Diagnóstico → antibiótico', 'field' => 'diag_ab', 'type' => 'min', 'class' => 'bg-[#5b7fb0]'],
                ['title' => 'Estancia en urgencias', 'field' => 'er_stay_hours', 'type' => 'hours', 'class' => 'bg-[#0f766e]'],
                ['title' => 'Mediana estancia en CDO', 'field' => 'clinic_stay_days', 'type' => 'days', 'class' => 'bg-[#17375e]'],
                ['title' => 'Mediana estancia en UCI', 'field' => 'uci_stay_days', 'type' => 'days', 'class' => 'bg-[#b45309]'],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($barCharts as $chart)
                @php
                    $vals = array_map(fn ($r) => $r[$chart['field']] ?? 0, $this->rows);
                    $max = max($chart['type'] === 'pct' ? 100 : 1, max($vals ?: [0]));
                @endphp
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $chart['title'] }}</h3>
                    <div class="flex items-end gap-2" style="height: 150px">
                        @foreach ($this->rows as $row)
                            @php
                                $raw = $row[$chart['field']];
                                $val = $raw ?? 0;
                                $pct = $max > 0 ? min(100, ($val / $max) * 100) : 0;
                                $barPx = max(4, (int) round($pct * 1.1));
                                $label = match ($chart['type']) {
                                    'min' => $raw === null ? '—' : $this->formatMinutes($raw),
                                    'pct' => $raw === null ? '—' : round($raw).'%',
                                    'hours' => $raw === null ? '—' : number_format($raw, 1, ',', '.'),
                                    'days' => $raw === null ? '—' : number_format($raw, 1, ',', '.'),
                                    default => $val,
                                };
                            @endphp
                            <div class="flex flex-1 flex-col items-center justify-end gap-1" title="{{ $row['month'] }}: {{ $label }}">
                                <span class="text-[10px] font-semibold text-gray-600">{{ $label }}</span>
                                <div class="w-full rounded-t {{ $chart['class'] }}" style="height: {{ $barPx }}px"></div>
                                <span class="text-[10px] font-medium text-gray-500">{{ substr($row['month'], 5) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tortas de distribución --}}
        @php
            $palette = ['#17375e', '#5b7fb0', '#0f766e', '#b45309', '#b91c1c', '#7c3aed', '#94a3b8', '#0891b2', '#a16207'];
            $pies = [
                ['title' => 'Toma de cultivo previo a antibiótico', 'data' => $this->distributions['culture_before']],
                ['title' => 'Ajuste de AB de acuerdo a cultivo', 'data' => $this->distributions['ab_adjusted']],
                ['title' => 'Foco infeccioso identificado', 'data' => $this->distributions['focus']],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ($pies as $pie)
                @php
                    $total = array_sum(array_column($pie['data'], 'value'));
                    $stops = [];
                    $cursor = 0;
                    foreach (array_values($pie['data']) as $idx => $item) {
                        if ($item['value'] <= 0) { continue; }
                        $color = $palette[$idx % count($palette)];
                        $start = $total > 0 ? ($cursor / $total) * 100 : 0;
                        $cursor += $item['value'];
                        $end = $total > 0 ? ($cursor / $total) * 100 : 0;
                        $stops[] = "{$color} {$start}% {$end}%";
                    }
                    $gradient = $stops ? implode(', ', $stops) : '#e2e8f0 0% 100%';
                @endphp
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $pie['title'] }}</h3>
                    <div class="flex items-center gap-5">
                        <div class="h-28 w-28 shrink-0 rounded-full" style="background: conic-gradient({{ $gradient }})"></div>
                        <div class="space-y-1 text-xs">
                            @foreach (array_values($pie['data']) as $idx => $item)
                                @if ($item['value'] > 0)
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-3 w-3 rounded-sm" style="background: {{ $palette[$idx % count($palette)] }}"></span>
                                        <span class="text-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                                        <span class="font-semibold text-gray-500">{{ $item['value'] }}{{ $total > 0 ? ' ('.round($item['value'] / $total * 100).'%)' : '' }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Costo de la atención --}}
        @php $cost = $this->costSummary; @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Costo de la atención</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                Análisis administrativo sobre los {{ $cost['count'] }} casos del período con costo total registrado.
                Independiente de los 6 indicadores institucionales; no los recalcula ni los sustituye.
            </p>

            @if ($cost['count'] > 0)
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    @php
                        $costCards = [
                            ['label' => 'Costo total', 'value' => $this->formatMoney($cost['total_cost'])],
                            ['label' => 'Costo promedio', 'value' => $this->formatMoney($cost['avg_cost'])],
                            ['label' => 'Costo mínimo', 'value' => $this->formatMoney($cost['min_cost'])],
                            ['label' => 'Costo máximo', 'value' => $this->formatMoney($cost['max_cost'])],
                            ['label' => 'Costo promedio por día de estancia', 'value' => $this->formatMoney($cost['avg_cost_per_stay_day'])],
                        ];
                    @endphp
                    @foreach ($costCards as $card)
                        <div class="rounded-2xl bg-gradient-to-br from-[#0d2340] to-[#17375e] p-5 text-white shadow-lg">
                            <span class="text-xs font-semibold uppercase tracking-wide text-white/75">{{ $card['label'] }}</span>
                            <div class="mt-3 text-2xl font-bold">{{ $card['value'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/30">
                        <span class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">Costo promedio · fallecidos</span>
                        <div class="mt-2 text-2xl font-bold text-red-700 dark:text-red-300">{{ $this->formatMoney($cost['avg_cost_deceased']) }}</div>
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">n = {{ $cost['count_deceased'] }} caso(s)</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
                        <span class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Costo promedio · vivos</span>
                        <div class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $this->formatMoney($cost['avg_cost_alive']) }}</div>
                        <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">n = {{ $cost['count_alive'] }} caso(s)</p>
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Top pacientes por costo</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                                    <th class="py-2 pr-3 font-semibold">#</th>
                                    <th class="py-2 pr-3 font-semibold">Caso</th>
                                    <th class="py-2 pr-3 font-semibold">Paciente</th>
                                    <th class="py-2 pr-3 font-semibold">Costo</th>
                                    <th class="py-2 pr-3 font-semibold">Estancia (días)</th>
                                    <th class="py-2 pr-3 font-semibold">Desenlace</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cost['top_cases'] as $idx => $topCase)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-3 font-semibold text-gray-400">{{ $idx + 1 }}</td>
                                        <td class="py-2 pr-3 font-semibold text-[#17375e] dark:text-gray-100">{{ $topCase->case_number }}</td>
                                        <td class="py-2 pr-3 text-gray-700 dark:text-gray-200">{{ $topCase->patient?->full_name ?? '—' }}</td>
                                        <td class="py-2 pr-3 font-bold text-gray-700 dark:text-gray-200">{{ $this->formatMoney($topCase->total_cost) }}</td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ number_format($topCase->stay_days, 1, ',', '.') }}</td>
                                        <td class="py-2 pr-3 font-semibold {{ $topCase->deceased ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $topCase->deceased ? 'Fallecido' : ($topCase->outcome_state ?: 'Vivo') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Ningún caso del período tiene costo total registrado todavía.</p>
            @endif
        </div>

        {{-- Evolución de severidad (NEWS2 y SOFA) --}}
        @php $severityMonthly = $this->severityMonthly; @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Severidad: NEWS2 y SOFA</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                Promedio mensual del valor máximo de NEWS2 y SOFA registrado por caso (de los tamizajes del caso).
                Análisis complementario, independiente de los 6 indicadores institucionales.
            </p>

            @if (collect($severityMonthly)->contains(fn ($row) => $row['avg_news2'] !== null || $row['avg_sofa'] !== null))
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ([['title' => 'NEWS2 promedio por mes', 'field' => 'avg_news2', 'max' => 20, 'class' => 'bg-[#b45309]'], ['title' => 'SOFA promedio por mes', 'field' => 'avg_sofa', 'max' => 24, 'class' => 'bg-[#7c3aed]']] as $chart)
                        @php
                            $vals = array_map(fn ($r) => $r[$chart['field']] ?? 0, $severityMonthly);
                            $max = max($chart['max'] * 0.4, max($vals ?: [0]));
                        @endphp
                        <div class="rounded-2xl border border-gray-100 p-4 dark:border-gray-800">
                            <h4 class="mb-3 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $chart['title'] }}</h4>
                            <div class="flex items-end gap-2" style="height: 120px">
                                @foreach ($severityMonthly as $row)
                                    @php
                                        $raw = $row[$chart['field']];
                                        $val = $raw ?? 0;
                                        $pct = $max > 0 ? min(100, ($val / $max) * 100) : 0;
                                        $barPx = max(4, (int) round($pct * 0.9));
                                    @endphp
                                    <div class="flex flex-1 flex-col items-center justify-end gap-1" title="{{ $row['month'] }}: {{ $raw ?? '—' }} (n={{ $row['n'] }})">
                                        <span class="text-[10px] font-semibold text-gray-600">{{ $raw ?? '—' }}</span>
                                        <div class="w-full rounded-t {{ $chart['class'] }}" style="height: {{ $barPx }}px"></div>
                                        <span class="text-[10px] font-medium text-gray-500">{{ substr($row['month'], 5) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Todavía no hay tamizajes con NEWS2 o SOFA registrados en este período.</p>
            @endif
        </div>

        {{-- Costo por banda de severidad --}}
        @php $costBySeverity = $this->costBySeverityBand; @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Costo por severidad</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Costo de los casos con NEWS2/SOFA y costo registrados, agrupados por su banda de severidad máxima.</p>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ([['title' => 'Por NEWS2', 'rows' => $costBySeverity['news2']], ['title' => 'Por SOFA', 'rows' => $costBySeverity['sofa']]] as $group)
                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">{{ $group['title'] }}</h4>
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                                    <th class="py-2 pr-3 font-semibold">Banda</th>
                                    <th class="py-2 pr-3 font-semibold">n</th>
                                    <th class="py-2 pr-3 font-semibold">Costo promedio</th>
                                    <th class="py-2 pr-3 font-semibold">Mín. — Máx.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rows'] as $row)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-3 font-semibold text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                        <td class="py-2 pr-3 text-gray-500">{{ $row['n'] }}</td>
                                        <td class="py-2 pr-3 font-bold text-[#17375e] dark:text-white">{{ $this->formatMoney($row['avg_cost']) }}</td>
                                        <td class="py-2 pr-3 text-gray-500">{{ $this->formatMoney($row['min_cost']) }} — {{ $this->formatMoney($row['max_cost']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mortalidad cruzada por severidad y costo --}}
        @php $crossTab = $this->mortalityCrossTab; @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Mortalidad cruzada por severidad y costo</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                Sobre {{ $crossTab['count'] }} caso(s) del período con costo registrado. Las bandas de costo son terciles de este mismo período
                (bajo/medio/alto), no montos fijos. Las bandas de NEWS2 y SOFA son de referencia.
            </p>

            @if ($crossTab['count'] > 0)
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @foreach ([['title' => 'NEWS2 × costo', 'rows' => $crossTab['news2']], ['title' => 'SOFA × costo', 'rows' => $crossTab['sofa']]] as $group)
                        <div>
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">{{ $group['title'] }}</h4>
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                                        <th class="py-2 pr-3 font-semibold">Severidad</th>
                                        @foreach ($crossTab['cost_bands'] as $costLabel)
                                            <th class="py-2 pr-3 font-semibold">{{ $costLabel }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['rows'] as $row)
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <td class="py-2 pr-3 font-semibold text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                            @foreach (array_keys($crossTab['cost_bands']) as $costKey)
                                                @php $cell = $row['cells'][$costKey]; @endphp
                                                <td class="py-2 pr-3">
                                                    @if ($cell['n'] > 0)
                                                        <span class="font-bold {{ $cell['mortality_pct'] >= 50 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-200' }}">{{ $cell['mortality_pct'] }}%</span>
                                                        <span class="text-gray-400">(n={{ $cell['n'] }})</span>
                                                    @else
                                                        <span class="text-gray-300">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Ningún caso del período tiene costo y severidad registrados simultáneamente.</p>
            @endif
        </div>

        {{-- Tiempo a control del foco por tipo --}}
        @php $sourceControlTiming = $this->sourceControlTiming; @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Tiempo a control del foco por tipo de foco infeccioso</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                Mediana de horas desde el tiempo cero hasta el primer procedimiento de control del foco registrado, por tipo de foco.
                Análisis complementario, independiente de los 6 indicadores institucionales.
            </p>
            @if (count($sourceControlTiming) > 0)
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                            <th class="py-2 pr-3 font-semibold">Foco</th>
                            <th class="py-2 pr-3 font-semibold">n</th>
                            <th class="py-2 pr-3 font-semibold">Mediana (horas)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sourceControlTiming as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-3 font-semibold text-gray-700 dark:text-gray-200">{{ $row['focus'] }}</td>
                                <td class="py-2 pr-3 text-gray-500">{{ $row['n'] }}</td>
                                <td class="py-2 pr-3 font-bold text-[#17375e] dark:text-white">{{ $row['median_hours'] }} h</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Ningún caso del período tiene un procedimiento de control del foco registrado todavía.</p>
            @endif
        </div>

        {{-- Detalle por paciente --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-1 text-sm font-bold text-[#17375e] dark:text-white">Detalle por paciente</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Casos del período seleccionado ({{ $this->cases->count() }}). Desliza horizontalmente para ver todas las columnas.</p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1400px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-700">
                            <th class="py-2 pr-3 font-semibold">Caso</th>
                            <th class="py-2 pr-3 font-semibold">Paciente</th>
                            <th class="py-2 pr-3 font-semibold">Documento</th>
                            <th class="py-2 pr-3 font-semibold">N.º ingreso</th>
                            <th class="py-2 pr-3 font-semibold">Activación del código</th>
                            <th class="py-2 pr-3 font-semibold">Toma de hemocultivos</th>
                            <th class="py-2 pr-3 font-semibold">Lactato</th>
                            <th class="py-2 pr-3 font-semibold">Antibiótico (hora)</th>
                            <th class="py-2 pr-3 font-semibold">Antibiótico(s) usado(s)</th>
                            <th class="py-2 pr-3 font-semibold">Foco</th>
                            <th class="py-2 pr-3 font-semibold">Choque</th>
                            <th class="py-2 pr-3 font-semibold">PAM 3 h</th>
                            <th class="py-2 pr-3 font-semibold">Urgencias (d)</th>
                            <th class="py-2 pr-3 font-semibold">CDO (d)</th>
                            <th class="py-2 pr-3 font-semibold">UCI (d)</th>
                            <th class="py-2 pr-3 font-semibold">Desenlace</th>
                            <th class="py-2 pr-3 font-semibold">NEWS2</th>
                            <th class="py-2 pr-3 font-semibold">SOFA</th>
                            <th class="py-2 pr-3 font-semibold">Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $severityByCase = $this->severityByCase; @endphp
                        @foreach ($this->cases as $case)
                            @php $severity = $severityByCase[$case->id] ?? ['news2' => null, 'sofa' => null]; @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-3 font-semibold text-[#17375e] dark:text-gray-100">{{ $case->case_number }}</td>
                                <td class="py-2 pr-3 text-gray-700 dark:text-gray-200">{{ $case->patient?->full_name ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->patient?->identification ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->admission_number ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->activation_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->culture_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->lactate_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->antibiotic_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->antibiotics_used ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->infection_focus ?? '—' }}</td>
                                <td class="py-2 pr-3">{{ $case->septic_shock ? 'Sí' : 'No' }}</td>
                                <td class="py-2 pr-3">{{ $case->septic_shock ? ($case->map_goal_met ? 'Sí' : 'No') : 'N/A' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->er_stay_days !== null ? number_format((float) $case->er_stay_days, 1, ',', '.') : '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->clinic_stay_days !== null ? number_format((float) $case->clinic_stay_days, 1, ',', '.') : '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $case->uci ? ($case->uci_stay_days !== null ? number_format((float) $case->uci_stay_days, 1, ',', '.') : 'UCI') : '—' }}</td>
                                <td class="py-2 pr-3 font-semibold {{ $case->deceased ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $case->deceased ? 'Fallecido' : ($case->outcome_state ?: 'Vivo') }}
                                </td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $severity['news2'] ?? '—' }}</td>
                                <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $severity['sofa'] ?? '—' }}</td>
                                <td class="py-2 pr-3 font-semibold text-gray-700 dark:text-gray-200">{{ $this->formatMoney($case->total_cost) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500 dark:bg-gray-900">
            No hay datos de Sepsis para el período seleccionado.
        </div>
    @endif
</x-filament-panels::page>
