<x-filament-panels::page>
    <div class="mx-auto w-full max-w-7xl space-y-4">
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-950">
            Esta plataforma apoya la trazabilidad y detección temprana a partir de los eventos reportados por las bombas de infusión. No sustituye el monitoreo clínico directo del paciente ni el juicio del equipo tratante.
        </div>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-bold uppercase text-slate-600">
                        <th class="px-3 py-2">Caso</th>
                        <th class="px-3 py-2">Cama</th>
                        <th class="px-3 py-2">Paciente</th>
                        <th class="px-3 py-2">Último evento</th>
                        <th class="px-3 py-2">Fármacos activos (últimos 5 eventos)</th>
                        <th class="px-3 py-2">Score VIS</th>
                        <th class="px-3 py-2">Alertas abiertas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->rows as $row)
                        @php($stay = $row['stay'])
                        @php($vis = $row['vis_current'])
                        @php($visPrev = $row['vis_previous'])
                        <tr>
                            <td class="px-3 py-2 font-bold text-slate-900">{{ $stay->case_number }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $stay->bed_label ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $stay->patient?->full_name ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                @if ($row['last_event'])
                                    {{ $row['last_event']->medication_name_raw }}
                                    <span class="block text-xs text-slate-500">{{ $row['last_event']->occurred_at?->format('d/m/Y H:i') }}</span>
                                @else
                                    Sin eventos
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">{{ $row['active_drugs']->implode(', ') ?: '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                @if ($vis)
                                    <span class="font-bold">{{ $vis->vis_score }}</span>
                                    @if ($visPrev)
                                        @if ($vis->vis_score > $visPrev->vis_score)
                                            <span class="text-red-600" title="En aumento">▲</span>
                                        @elseif ($vis->vis_score < $visPrev->vis_score)
                                            <span class="text-emerald-600" title="En descenso">▼</span>
                                        @else
                                            <span class="text-slate-400" title="Sin cambio">–</span>
                                        @endif
                                    @endif
                                @else
                                    Sin dato (falta peso registrado)
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($row['open_alerts']->isEmpty())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Sin alertas</span>
                                @else
                                    @php($hasCritical = $row['open_alerts']->contains(fn ($a) => $a->severity === 'critical'))
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-red-100 text-red-800' => $hasCritical,
                                        'bg-amber-100 text-amber-800' => ! $hasCritical,
                                    ])>
                                        {{ $row['open_alerts']->count() }} abierta(s)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">No hay estancias activas con eventos de bomba registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
