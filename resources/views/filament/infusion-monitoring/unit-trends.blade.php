<x-filament-panels::page>
    @php
        $ramp = ['#cde2fb', '#9ec5f4', '#6da7ec', '#3987e5', '#256abf', '#184f95', '#0d366b'];
        $cellColor = function (?float $value, float $min, float $max) use ($ramp) {
            if ($value === null) {
                return null;
            }
            if ($max <= $min) {
                return $ramp[3];
            }
            $ratio = ($value - $min) / ($max - $min);
            $index = (int) round($ratio * (count($ramp) - 1));

            return $ramp[max(0, min(count($ramp) - 1, $index))];
        };
        $textColor = fn (?string $hex) => $hex && in_array($hex, ['#256abf', '#184f95', '#0d366b'], true) ? '#ffffff' : '#0b0b0b';
    @endphp

    <p class="text-sm text-slate-700">
        Comparativo semanal entre unidades — útil para detectar si una unidad concentra más carga vasoactiva o más
        alertas que otras, no solo la tendencia de un paciente individual. La dosis acumulada no se mezcla entre
        unidades porque cada fármaco tiene su propia unidad de medida (mg vs. unidades).
    </p>

    @foreach ([['title' => 'Score VIS promedio por semana', 'data' => $this->visTrend, 'unitLabel' => 'VIS'], ['title' => 'Alertas clínicas abiertas por semana', 'data' => $this->alertTrend, 'unitLabel' => 'alertas']] as $section)
        <div class="mt-5">
            <h3 class="mb-2 text-sm font-bold text-slate-800">{{ $section['title'] }}</h3>
            @if (empty($section['data']['units']))
                <p class="rounded-xl border bg-white px-4 py-6 text-center text-sm text-slate-500">Sin datos suficientes todavía (requiere estancias con unidad asignada).</p>
            @else
                <div class="overflow-x-auto rounded-xl border bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Unidad</th>
                                @foreach ($section['data']['weeks'] as $week)
                                    <th class="px-3 py-3 text-center">Sem. {{ \Illuminate\Support\Carbon::parse($week)->format('d-M') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($section['data']['units'] as $row)
                                @php
                                    $vals = array_filter($row['values'], fn ($v) => $v !== null);
                                    $min = $vals ? min($vals) : 0;
                                    $max = $vals ? max($vals) : 0;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $row['unit'] }}</td>
                                    @foreach ($section['data']['weeks'] as $week)
                                        @php
                                            $value = $row['values'][$week] ?? null;
                                            $bg = $cellColor($value, (float) $min, (float) $max);
                                        @endphp
                                        <td class="px-3 py-3 text-center">
                                            @if ($value === null)
                                                <span class="text-slate-300">—</span>
                                            @else
                                                <span class="inline-block rounded px-2 py-1 font-bold" style="background:{{ $bg }}; color:{{ $textColor($bg) }}">{{ $value }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
</x-filament-panels::page>
