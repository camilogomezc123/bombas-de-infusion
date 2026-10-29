<x-filament-panels::page>
    <div class="mx-auto w-full max-w-7xl space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Estancia</label>
            <select wire:model.live="icuStayId" class="fi-select-input block w-full rounded-lg border-slate-300 text-sm">
                <option value="">Seleccione una estancia activa…</option>
                @foreach ($this->stays as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @php($stay = $this->selectedStay)

        @if ($stay)
            <form wire:submit="saveWeight" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-bold text-slate-900">Registrar peso</p>
                {{ $this->weightForm }}
                <div class="mt-3">
                    <button type="submit" class="rounded-lg bg-[#e11d48] px-4 py-2 text-sm font-bold text-white">Guardar peso</button>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-slate-500">Peso más reciente</p>
                    @php($weight = $stay->weights->first())
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $weight?->weight_kg ?? '—' }} kg</p>
                    <p class="text-xs text-slate-500">{{ $weight ? $weight->recorded_at->format('d/m/Y H:i') : 'Sin peso registrado — VIS y dosis/kg no se pueden calcular' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-slate-500">Score VIS más reciente</p>
                    @php($latestVis = $stay->visScoreSnapshots->first())
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $latestVis?->vis_score ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ $latestVis ? $latestVis->computed_at->format('d/m/Y H:i') : 'Sin cálculo' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-slate-500">Alertas abiertas</p>
                    @php($openAlerts = $stay->infusionClinicalAlerts->where('status', '!=', 'resolved'))
                    <p class="mt-1 text-2xl font-bold {{ $openAlerts->isEmpty() ? 'text-slate-900' : 'text-red-600' }}">{{ $openAlerts->count() }}</p>
                    <p class="text-xs text-slate-500">Reconózcalas o resuélvalas desde "Alertas clínicas"</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-bold text-slate-900">Tendencia del score VIS (más reciente primero)</p>
                @if ($stay->visScoreSnapshots->isEmpty())
                    <p class="text-sm text-slate-500">Sin cálculos de VIS todavía.</p>
                @else
                    <div class="flex flex-wrap items-end gap-2">
                        @foreach ($stay->visScoreSnapshots->reverse() as $snapshot)
                            <div class="flex flex-col items-center">
                                <div class="w-8 rounded-t bg-cyan-500" style="height: {{ max(4, min(120, $snapshot->vis_score * 2)) }}px"></div>
                                <span class="mt-1 text-[10px] text-slate-500">{{ $snapshot->computed_at->format('d/m H:i') }}</span>
                                <span class="text-[10px] font-bold text-slate-700">{{ $snapshot->vis_score }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="mb-2 text-sm font-bold text-slate-900">Dosis acumulada por fármaco y día</p>
                @forelse ($this->doseSummaries as $drug => $summaries)
                    <div class="mb-3">
                        <p class="text-xs font-bold uppercase text-slate-600">{{ $drug }}</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="text-left text-slate-500">
                                        <th class="pr-4 py-1">Fecha</th>
                                        <th class="pr-4 py-1">Dosis acumulada</th>
                                        <th class="pr-4 py-1">Tasa máxima observada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($summaries as $summary)
                                        <tr class="border-t border-slate-100">
                                            <td class="pr-4 py-1">{{ $summary->summary_date->format('d/m/Y') }}</td>
                                            <td class="pr-4 py-1">{{ $summary->cumulative_dose }} {{ $summary->dose_unit }}</td>
                                            <td class="pr-4 py-1">{{ $summary->max_rate_seen ?? '—' }} {{ $summary->max_rate_unit }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sin dosis acumulada calculada todavía.</p>
                @endforelse
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold uppercase text-slate-600">
                            <th class="px-3 py-2">Fecha/hora</th>
                            <th class="px-3 py-2">Medicamento</th>
                            <th class="px-3 py-2">Concentración</th>
                            <th class="px-3 py-2">Alerta</th>
                            <th class="px-3 py-2">Próxima acción</th>
                            <th class="px-3 py-2">Intento de valor</th>
                            <th class="px-3 py-2">Límites</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($stay->infusionEvents as $event)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $event->occurred_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-900">{{ $event->medication_name_raw }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $event->concentration_raw ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if ($event->alert_level)
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-bold',
                                            'bg-red-100 text-red-800' => str_contains($event->alert_level, 'Duro'),
                                            'bg-amber-100 text-amber-800' => str_contains($event->alert_level, 'Blando'),
                                        ])>{{ $event->alert_level }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-700">{{ $event->next_action ?? '—' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $event->attempted_value !== null ? $event->attempted_value.' '.$event->attempted_unit : '—' }}</td>
                                <td class="px-3 py-2 text-slate-700">
                                    @if ($event->hard_limit_low !== null || $event->hard_limit_high !== null)
                                        Duro: {{ $event->hard_limit_low }} - {{ $event->hard_limit_high }}
                                    @elseif ($event->soft_limit_low !== null || $event->soft_limit_high !== null)
                                        Blando: {{ $event->soft_limit_low }} - {{ $event->soft_limit_high }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">Sin eventos registrados para esta estancia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">Seleccione una estancia para ver su bitácora de infusión.</p>
        @endif
    </div>
</x-filament-panels::page>
